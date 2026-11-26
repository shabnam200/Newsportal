<?php

namespace App\Http\Controllers;

use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Throwable;

class NewsEntryController extends Controller
{
    public function create(Request $request, SubscriptionService $subs, PortalBillingController $billing)
    {
        $types = DB::connection('tenant')->table('news_type_info')->where('type_status', 1)
            ->orderBy('news_type_id')->get(['news_type_id', 'type_name']);

        return Inertia::render('Portal/NewsEntry', [
            'newsTypes' => $types,
            'statusOptions' => config('news.status'),
            'defaultStatus' => config('news.default_status'),
            'categoriesUrl' => route('categories.data'),
            'reportersUrl' => route('news.reporters'),
            'storeUrl' => route('news.store'),
            'choicesUrl' => asset('vendors/choices/choices.min.js'),
            'jqueryUrl' => asset('vendors/jquery/jquery.min.js'),
            'summernoteUrl' => asset('vendors/summernote/summernote-lite.min.js'),
            'summernoteCssUrl' => asset('vendors/summernote/summernote-lite.min.css'),
        ])->rootView('dashboard')->withViewData([
            ...$billing->dashboardViewData($request, $subs),
            'module' => 'news-entry',
            'newsTypes' => $types,
        ]);
    }

    public function reporters(): JsonResponse
    {
        // Reporter names already used on this portal's news (one row per reporter in news_writer_info).
        $rows = DB::connection('tenant')->table('news_writer_info')
            ->whereNotNull('author_name')->where('author_name', '<>', '')
            ->distinct()->orderBy('author_name')->pluck('author_name')->values();

        return response()->json(['data' => $rows]);
    }

    public function store(Request $request): JsonResponse
    {
        $db = DB::connection('tenant');
        $portalId = (int) $request->attributes->get('portal')->id;
        $validator = Validator::make($request->all(), [
            'cat_id' => ['required', 'integer'], 'cat_child_1' => ['nullable', 'integer'], 'cat_child_2' => ['nullable', 'integer'],
            'news_status' => ['required', 'integer'], 'news_type' => ['required', 'integer'],
            'headline_tag' => ['nullable', 'string', 'max:100'], 'news_headline' => ['required', 'string', 'max:250'],
            'news_sub_headline' => ['nullable', 'string', 'max:400'], 'news_details_brief' => ['nullable', 'string', 'max:400'],
            'sections' => ['required', 'array', 'min:1', 'max:30'], 'sections.*.media_type' => ['required', 'in:1,2'],
            'sections.*.text' => ['nullable', 'string'], 'sections.*.caption' => ['nullable', 'string', 'max:300'],
            'sections.*.image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'sections.*.video_link' => ['nullable', 'url:http,https', 'max:255'], 'sections.*.is_cover' => ['nullable', 'boolean'],
            'tags' => ['nullable', 'array', 'max:20'], 'tags.*' => ['string', 'max:250'],
            'reporter_names' => ['nullable', 'array', 'max:20'], 'reporter_names.*' => ['nullable', 'string', 'max:100'], 'reporter_name' => ['nullable', 'string', 'max:250'],
            'news_source' => ['nullable', 'string', 'max:150'], 'news_source_link' => ['nullable', 'url:http,https', 'max:500'],
            'seo_title' => ['nullable', 'string', 'max:250'], 'seo_keyword' => ['nullable', 'string', 'max:350'], 'seo_description' => ['nullable', 'string', 'max:400'],
        ]);
        $data = $validator->validate();
        $cat = $db->table('categories')->where('id', $data['cat_id'])->where('level', 1)->where('status', 1)->first();
        if (!$cat) $validator->errors()->add('cat_id', 'Choose an active category.');
        if (!empty($data['cat_child_1'])) {
            $child = $db->table('categories')->where('id', $data['cat_child_1'])->where('parent_id', $data['cat_id'])->where('level', 2)->where('status', 1)->first();
            if (!$child) $validator->errors()->add('cat_child_1', 'Choose a sub-category under the selected category.');
        }
        if (!empty($data['cat_child_2'])) {
            $child = $db->table('categories')->where('id', $data['cat_child_2'])->where('parent_id', $data['cat_child_1'] ?? 0)->where('level', 3)->where('status', 1)->first();
            if (!$child) $validator->errors()->add('cat_child_2', 'Choose a sub-sub-category under the selected sub-category.');
        }
        if (!array_key_exists((int) $data['news_status'], config('news.status'))) $validator->errors()->add('news_status', 'Choose a valid status.');
        if (!$db->table('news_type_info')->where('news_type_id', $data['news_type'])->where('type_status', 1)->exists()) $validator->errors()->add('news_type', 'Choose an active news type.');
        // Names picked in the dropdowns plus any new names typed in the box (several allowed, separated by commas).
        $picked = array_merge($data['reporter_names'] ?? [], explode(',', (string) ($data['reporter_name'] ?? '')));
        $reporterNames = [];
        foreach ($picked as $name) {
            $name = trim(preg_replace('/\s+/u', ' ', (string) $name));
            if ($name !== '' && mb_strlen($name) <= 100) $reporterNames[mb_strtolower($name)] ??= $name;
        }
        $reporterNames = array_values($reporterNames);
        $images = [];
        foreach ($data['sections'] as $i => $section) {
            if ($section['media_type'] == 1 && !$request->file("sections.{$i}.image")) $validator->errors()->add("sections.{$i}.image", 'Choose an image.');
            if ($section['media_type'] == 2 && empty($section['video_link'])) $validator->errors()->add("sections.{$i}.video_link", 'Enter a video link.');
            if ($section['media_type'] == 1) $images[] = $i;
        }
        if ($images) {
            $covers = array_values(array_filter($images, fn ($i) => !empty($data['sections'][$i]['is_cover'])));
            if (count($covers) !== 1) $validator->errors()->add('sections', 'Choose exactly one cover image.');
        }
        if (!$reporterNames) $validator->errors()->add('reporter_name', 'Choose at least one reporter or enter a reporter name.');
        if ($validator->errors()->isNotEmpty()) throw new ValidationException($validator);

        $zone = $db->table('app_config')->value('time_zone') ?: 'Asia/Dhaka';
        $now = now($zone);
        $written = [];
        try {
            $id = $db->transaction(function () use ($db, $request, $data, $portalId, $now, $reporterNames, &$written) {
                $coverIndex = null;
                foreach ($data['sections'] as $i => $s) if (($s['media_type'] == 1) && !empty($s['is_cover'])) $coverIndex = $i;
                $reporterText = implode(', ', $reporterNames);
                $id = $db->table('news_common_info')->insertGetId([
                    'news_headline' => $data['news_headline'], 'headline_tag' => $data['headline_tag'] ?? '', 'news_details_brief' => $data['news_details_brief'] ?? '',
                    'news_reporter' => mb_substr($reporterText, 0, 100), 'author_id' => null,
                    'img_ext' => $coverIndex === null ? '' : '.' . $request->file("sections.{$coverIndex}.image")->guessExtension(),
                    'cat_id' => $data['cat_id'], 'cat_child_1' => $data['cat_child_1'] ?? 0, 'cat_child_2' => $data['cat_child_2'] ?? 0,
                    'page_id' => null, 'news_status' => $data['news_status'], 'news_type' => $data['news_type'], 'spc_type' => 0,
                    'catStatus' => 1, 'latestStatus' => 1, 'news_pub_date' => $now->toDateString(), 'news_pub_time' => $now->format('H:i:s'),
                    'news_mod_date' => $now->toDateString(), 'news_mod_time' => $now->format('H:i:s'), 'news_publisher' => 0, 'news_approver' => 0,
                ]);
                $slug = Str::lower(trim($data['news_headline']));
                $slug = preg_replace('/\s+/u', '-', $slug);
                $slug = preg_replace('/[^\p{L}\p{N}\p{M}-]+/u', '', $slug);
                $slug = mb_substr(preg_replace('/-+/u', '-', $slug), 0, 200) ?: 'news-' . $id;
                $details = '';
                $coverCaption = '';
                foreach ($data['sections'] as $i => $section) {
                    $num = $i + 1; $caption = $section['caption'] ?? '';
                    $details .= '<div class="news-section" data-section="' . $num . '">';
                    $safeText = $this->sanitizeHtml($section['text'] ?? '');
                    $plainText = html_entity_decode(strip_tags($safeText), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    if (trim($plainText) !== '') $details .= $safeText;
                    $path = ''; $video = '';
                    if ($section['media_type'] == 1) {
                        $file = $request->file("sections.{$i}.image");
                        $ext = strtolower($file->guessExtension());
                        $dir = public_path("images/news/{$portalId}/" . $now->format('Y/m'));
                        File::ensureDirectoryExists($dir);
                        $filename = "{$id}_{$num}.{$ext}";
                        $file->move($dir, $filename);
                        $path = "images/news/{$portalId}/" . $now->format('Y/m') . "/{$filename}";
                        $written[] = public_path($path);
                        if ($i === $coverIndex) $coverCaption = $caption;
                        $details .= '<figure class="news-media"><img src="/' . e($path) . '" alt="' . e($caption) . '">';
                        if ($caption !== '') $details .= '<figcaption>' . e($caption) . '</figcaption>';
                        $details .= '</figure>';
                    } else {
                        $video = $section['video_link'];
                        $details .= $this->videoHtml($video);
                        if ($caption !== '') $details .= '<p class="news-video-caption">' . e($caption) . '</p>';
                    }
                    $details .= '</div>';
                    $db->table('news_image_info')->insert(['news_id' => $id, 'section_no' => $num, 'media_type' => $section['media_type'], 'img_path' => $path, 'video_link' => $video, 'caption' => $caption, 'is_cover' => $i === $coverIndex ? 1 : 0]);
                }
                $db->table('news_info')->insert(['news_id' => $id, 'news_slug' => $slug, 'news_sub_headline' => $data['news_sub_headline'] ?? '', 'news_details' => $details, 'img_caption' => mb_substr($coverCaption, 0, 150), 'video_link' => null, 'video_caption' => null, 'audio_link' => null, 'audio_caption' => null, 'news_source' => $data['news_source'] ?? '', 'news_source_link' => $data['news_source_link'] ?? '', 'news_area' => null, 'news_zone' => null, 'copy_protection_status' => 0, 'division_id' => null, 'district_id' => null, 'upazila_id' => null]);
                foreach (array_unique(array_filter(array_map('trim', $data['tags'] ?? []))) as $tag) $db->table('news_tag_info')->insert(['news_id' => $id, 'news_tag' => $tag, 'relevant_article' => '']);
                foreach ($reporterNames as $reporterName) $db->table('news_writer_info')->insert(['news_id' => $id, 'writer_type' => 1, 'author_ids' => null, 'author_name' => $reporterName]);
                $db->table('news_seo_info')->insert(['news_id' => $id, 'seo_title' => $data['seo_title'] ?? '', 'seo_keyword' => $data['seo_keyword'] ?? '', 'seo_description' => $data['seo_description'] ?? '']);
                return $id;
            });
        } catch (Throwable $e) {
            File::delete($written);
            throw $e;
        }
        return response()->json(['message' => 'News created successfully.', 'id' => $id], 201);
    }

    private function sanitizeHtml(string $html): string
    {
        $doc = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8" ?><div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $allowed = ['p','br','strong','b','em','i','u','s','ul','ol','li','a','h2','h3','h4','blockquote','span'];
        $walk = function ($node) use (&$walk, $allowed, $doc) {
            foreach (iterator_to_array($node->childNodes) as $child) {
                if ($child instanceof \DOMElement) {
                    if (!in_array(strtolower($child->tagName), $allowed, true)) {
                        $child->parentNode->replaceChild($doc->createTextNode($child->textContent), $child); continue;
                    }
                    foreach (iterator_to_array($child->attributes) as $attr) {
                        if ($child->tagName !== 'a' || $attr->name !== 'href') $child->removeAttribute($attr->name);
                    }
                    if ($child->tagName === 'a') {
                        $href = $child->getAttribute('href');
                        if (!preg_match('/^(https?:|mailto:)/i', $href)) $child->removeAttribute('href');
                        else $child->setAttribute('rel', 'nofollow noopener');
                    }
                    $walk($child);
                }
            }
        };
        $root = $doc->getElementsByTagName('div')->item(0); $walk($root);
        $out = ''; foreach ($root->childNodes as $child) $out .= $doc->saveHTML($child);
        return $out;
    }

    private function videoHtml(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST); $id = null;
        if (in_array($host, ['youtu.be', 'www.youtu.be'], true)) $id = trim(parse_url($url, PHP_URL_PATH), '/');
        elseif (in_array($host, ['youtube.com','www.youtube.com','m.youtube.com'], true)) {
            parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $q); $id = $q['v'] ?? null;
            if (!$id && preg_match('~^/(?:shorts|embed)/([^/]+)~', parse_url($url, PHP_URL_PATH), $m)) $id = $m[1];
        }
        if ($id && preg_match('/^[A-Za-z0-9_-]{6,20}$/', $id)) return '<div class="news-video"><iframe src="https://www.youtube.com/embed/' . e($id) . '" allowfullscreen loading="lazy"></iframe></div>';
        return '<div class="news-video"><a href="' . e($url) . '" rel="nofollow noopener">' . e($url) . '</a></div>';
    }
}
