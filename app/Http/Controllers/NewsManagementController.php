<?php

namespace App\Http\Controllers;

use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class NewsManagementController extends Controller
{
    public function index(Request $request, SubscriptionService $subs, PortalBillingController $billing)
    {
        $db = DB::connection('tenant');
        $q = trim((string) $request->query('q', ''));
        $status = $request->query('status', '');
        $cover = $db->table('news_image_info')->select('news_id')->selectRaw('MIN(img_path) AS img_path')
            ->where('is_cover', 1)->groupBy('news_id');

        $news = $db->table('news_common_info as n')
            ->leftJoin('news_info as i', 'i.news_id', '=', 'n.news_id')
            ->leftJoin('categories as c', 'c.id', '=', 'n.cat_id')
            ->leftJoin('news_type_info as t', 't.news_type_id', '=', 'n.news_type')
            ->leftJoinSub($cover, 'cover', fn ($join) => $join->on('cover.news_id', '=', 'n.news_id'))
            ->select([
                'n.news_id', 'n.news_headline', 'n.news_details_brief', 'n.news_reporter', 'n.news_status',
                'n.news_type', 'n.news_pub_date', 'n.news_pub_time', 'c.name as category_name',
                't.type_name', 'cover.img_path as cover_path', 'i.news_sub_headline',
            ])
            ->when($q !== '', fn ($query) => $query->where(function ($where) use ($q) {
                $where->where('n.news_headline', 'like', "%{$q}%")
                    ->orWhere('n.news_details_brief', 'like', "%{$q}%")
                    ->orWhere('n.news_reporter', 'like', "%{$q}%");
            }))
            ->when(in_array($status, ['0', '1'], true), fn ($query) => $query->where('n.news_status', (int) $status))
            ->orderByDesc('n.news_pub_date')->orderByDesc('n.news_pub_time')->orderByDesc('n.news_id')
            ->paginate(20)->withQueryString();

        $news->through(function ($item) {
            $item->edit_url = route('news.edit', $item->news_id);
            $item->delete_url = route('news.destroy', $item->news_id);
            $item->cover_url = $item->cover_path ? url('/' . ltrim($item->cover_path, '/\\')) : null;
            $item->status_label = config('news.status')[(int) $item->news_status] ?? 'Unknown';
            $item->published_at = trim((string) $item->news_pub_date . ' ' . substr((string) $item->news_pub_time, 0, 5));

            return $item;
        });

        return Inertia::render('Portal/NewsManagement', [
            'news' => $news,
            'filters' => ['q' => $q, 'status' => $status],
            'statusOptions' => config('news.status'),
            'createUrl' => route('news.create'),
            'managementUrl' => route('news.index'),
        ])->rootView('dashboard')->withViewData([
            ...$billing->dashboardViewData($request, $subs),
            'module' => 'news-management',
        ]);
    }

    public function edit(int $id, Request $request, SubscriptionService $subs, PortalBillingController $billing)
    {
        $db = DB::connection('tenant');
        $news = $db->table('news_common_info')->where('news_id', $id)->first();
        abort_unless($news, 404);

        $info = $db->table('news_info')->where('news_id', $id)->first();
        $mediaRows = $db->table('news_image_info')->where('news_id', $id)->orderBy('section_no')->get();
        $texts = $this->sectionTexts((string) ($info->news_details ?? ''));
        $sections = $mediaRows->map(function ($media) use ($texts) {
            return [
                'section_no' => (int) $media->section_no,
                'media_type' => (int) $media->media_type,
                'caption' => $media->caption,
                'is_cover' => (bool) $media->is_cover,
                'media_url' => $media->img_path ? url('/' . ltrim($media->img_path, '/\\')) : null,
                'video_link' => $media->video_link,
                'text' => $this->sanitizeHtml($texts[(int) $media->section_no] ?? ''),
            ];
        })->values();
        if ($sections->isEmpty()) {
            $sections = collect([[
                'section_no' => 1, 'media_type' => 0, 'caption' => '', 'is_cover' => false,
                'media_url' => null, 'video_link' => '', 'text' => $this->sanitizeHtml($texts[1] ?? (string) ($info->news_details ?? '')),
            ]]);
        }

        $writerNames = $db->table('news_writer_info')->where('news_id', $id)->where('writer_type', 1)
            ->whereNotNull('author_name')->where('author_name', '<>', '')->orderBy('news_writer_id')->pluck('author_name');
        $seo = $db->table('news_seo_info')->where('news_id', $id)->first();

        return Inertia::render('Portal/NewsEdit', [
            'newsId' => $id,
            'article' => [
                'news_headline' => $news->news_headline,
                'headline_tag' => $news->headline_tag,
                'news_sub_headline' => $info->news_sub_headline ?? '',
                'news_details_brief' => $news->news_details_brief,
                'news_status' => (int) $news->news_status,
                'news_type' => (int) $news->news_type,
                'cat_id' => (int) $news->cat_id,
                'cat_child_1' => (int) $news->cat_child_1,
                'cat_child_2' => (int) $news->cat_child_2,
                'reporter_names' => $writerNames->implode(', '),
                'tags' => $db->table('news_tag_info')->where('news_id', $id)->orderBy('news_tag_id')->pluck('news_tag')->implode(', '),
                'news_source' => $info->news_source ?? '',
                'news_source_link' => $info->news_source_link ?? '',
                'seo_title' => $seo->seo_title ?? '',
                'seo_keyword' => $seo->seo_keyword ?? '',
                'seo_description' => $seo->seo_description ?? '',
            ],
            'sections' => $sections,
            'categories' => $db->table('categories')->orderBy('level')->orderBy('name')
                ->get(['id', 'parent_id', 'level', 'name', 'status']),
            'newsTypes' => $db->table('news_type_info')->orderBy('news_type_id')->get(['news_type_id', 'type_name', 'type_status']),
            'statusOptions' => config('news.status'),
            'updateUrl' => route('news.update', $id),
            'managementUrl' => route('news.index'),
        ])->rootView('dashboard')->withViewData([
            ...$billing->dashboardViewData($request, $subs),
            'module' => 'news-management',
        ]);
    }

    public function update(Request $request, int $id)
    {
        $db = DB::connection('tenant');
        $common = $db->table('news_common_info')->where('news_id', $id)->first();
        abort_unless($common, 404);

        $mediaRows = $db->table('news_image_info')->where('news_id', $id)->orderBy('section_no')->get();
        $sectionCount = max(1, $mediaRows->count());
        $validator = Validator::make($request->all(), [
            'cat_id' => ['required', 'integer'], 'cat_child_1' => ['nullable', 'integer'], 'cat_child_2' => ['nullable', 'integer'],
            'news_status' => ['required', 'integer'], 'news_type' => ['required', 'integer'],
            'headline_tag' => ['nullable', 'string', 'max:100'], 'news_headline' => ['required', 'string', 'max:250'],
            'news_sub_headline' => ['nullable', 'string', 'max:400'], 'news_details_brief' => ['nullable', 'string', 'max:400'],
            'reporter_names' => ['nullable', 'string', 'max:1000'], 'tags' => ['nullable', 'string', 'max:5000'],
            'news_source' => ['nullable', 'string', 'max:150'], 'news_source_link' => ['nullable', 'url:http,https', 'max:500'],
            'seo_title' => ['nullable', 'string', 'max:250'], 'seo_keyword' => ['nullable', 'string', 'max:350'], 'seo_description' => ['nullable', 'string', 'max:400'],
            'sections' => ['required', 'array', 'size:' . $sectionCount], 'sections.*.text' => ['nullable', 'string'],
            'sections.*.caption' => ['nullable', 'string', 'max:300'],
        ]);
        $data = $validator->validate();

        $category = $db->table('categories')->where('id', $data['cat_id'])->where('level', 1)->first();
        if (!$category) $validator->errors()->add('cat_id', 'Choose a valid category.');
        if (!empty($data['cat_child_1']) && !$db->table('categories')->where('id', $data['cat_child_1'])->where('parent_id', $data['cat_id'])->where('level', 2)->exists()) {
            $validator->errors()->add('cat_child_1', 'Choose a sub-category under the selected category.');
        }
        if (!empty($data['cat_child_2']) && !$db->table('categories')->where('id', $data['cat_child_2'])->where('parent_id', $data['cat_child_1'] ?? 0)->where('level', 3)->exists()) {
            $validator->errors()->add('cat_child_2', 'Choose a sub-sub-category under the selected sub-category.');
        }
        if (!array_key_exists((int) $data['news_status'], config('news.status'))) $validator->errors()->add('news_status', 'Choose a valid status.');
        if (!$db->table('news_type_info')->where('news_type_id', $data['news_type'])->exists()) $validator->errors()->add('news_type', 'Choose a valid news type.');
        if ($validator->errors()->isNotEmpty()) throw new ValidationException($validator);

        $reporters = $this->splitNames($data['reporter_names'] ?? '', 20, 100);
        $tags = $this->splitNames($data['tags'] ?? '', 20, 250);
        if (!$reporters) {
            $validator->errors()->add('reporter_names', 'Enter at least one reporter name.');
            throw new ValidationException($validator);
        }

        $zone = $db->table('app_config')->value('time_zone') ?: 'Asia/Dhaka';
        $now = now($zone);
        $info = $db->table('news_info')->where('news_id', $id)->first();
        $details = '';
        $coverCaption = $info->img_caption ?? '';
        foreach ($mediaRows as $index => $media) {
            $section = $data['sections'][$index];
            $number = (int) $media->section_no;
            $caption = trim((string) ($section['caption'] ?? ''));
            $text = $this->sanitizeHtml((string) ($section['text'] ?? ''));
            $details .= '<div class="news-section" data-section="' . $number . '">' . $text;

            if ((int) $media->media_type === 1) {
                $path = (string) $media->img_path;
                $imageUrl = url('/' . ltrim($path, '/\\'));
                $details .= '<figure class="news-media"><img src="' . e($imageUrl) . '" alt="' . e($caption) . '">';
                if ($caption !== '') $details .= '<figcaption>' . e($caption) . '</figcaption>';
                $details .= '</figure>';
                if ((bool) $media->is_cover) $coverCaption = $caption;
            } elseif ((int) $media->media_type === 2) {
                $details .= $this->videoHtml((string) $media->video_link);
                if ($caption !== '') $details .= '<p class="news-video-caption">' . e($caption) . '</p>';
            }
            $details .= '</div>';
        }

        if ($mediaRows->isEmpty()) {
            $details = '<div class="news-section" data-section="1">' . $this->sanitizeHtml((string) ($data['sections'][0]['text'] ?? '')) . '</div>';
        }

        $db->transaction(function () use ($db, $id, $data, $reporters, $tags, $now, $details, $coverCaption, $mediaRows) {
            $reporterText = implode(', ', $reporters);
            $db->table('news_common_info')->where('news_id', $id)->update([
                'news_headline' => $data['news_headline'], 'headline_tag' => $data['headline_tag'] ?? '',
                'news_details_brief' => $data['news_details_brief'] ?? '', 'news_reporter' => mb_substr($reporterText, 0, 100),
                'cat_id' => $data['cat_id'], 'cat_child_1' => $data['cat_child_1'] ?? 0, 'cat_child_2' => $data['cat_child_2'] ?? 0,
                'news_status' => $data['news_status'], 'news_type' => $data['news_type'],
                'news_mod_date' => $now->toDateString(), 'news_mod_time' => $now->format('H:i:s'),
            ]);

            $infoUpdate = [
                'news_slug' => Str::slug($data['news_headline']) ?: 'news-' . $id,
                'news_sub_headline' => $data['news_sub_headline'] ?? '', 'news_details' => $details,
                'img_caption' => mb_substr($coverCaption, 0, 150), 'news_source' => $data['news_source'] ?? '',
                'news_source_link' => $data['news_source_link'] ?? '',
            ];
            foreach ($mediaRows as $index => $media) {
                $db->table('news_image_info')->where('id', $media->id)->update([
                    'caption' => trim((string) ($data['sections'][$index]['caption'] ?? '')),
                ]);
            }
            if ($db->table('news_info')->where('news_id', $id)->exists()) {
                $db->table('news_info')->where('news_id', $id)->update($infoUpdate);
            } else {
                $slug = Str::slug($data['news_headline']) ?: 'news-' . $id;
                $db->table('news_info')->insert($infoUpdate + [
                    'news_id' => $id, 'news_slug' => $slug, 'video_link' => null, 'video_caption' => null,
                    'audio_link' => null, 'audio_caption' => null, 'news_area' => null, 'news_zone' => null,
                    'copy_protection_status' => 0, 'division_id' => null, 'district_id' => null, 'upazila_id' => null,
                ]);
            }

            $db->table('news_tag_info')->where('news_id', $id)->delete();
            foreach ($tags as $tag) $db->table('news_tag_info')->insert(['news_id' => $id, 'news_tag' => $tag, 'relevant_article' => '']);

            $db->table('news_writer_info')->where('news_id', $id)->where('writer_type', 1)->delete();
            foreach ($reporters as $name) $db->table('news_writer_info')->insert([
                'news_id' => $id, 'writer_type' => 1, 'author_ids' => null, 'author_name' => $name,
            ]);

            $seo = [
                'seo_title' => $data['seo_title'] ?? '', 'seo_keyword' => $data['seo_keyword'] ?? '',
                'seo_description' => $data['seo_description'] ?? '',
            ];
            $seoId = $db->table('news_seo_info')->where('news_id', $id)->value('news_seo_id');
            if ($seoId) $db->table('news_seo_info')->where('news_seo_id', $seoId)->update($seo);
            else $db->table('news_seo_info')->insert($seo + ['news_id' => $id]);
        });

        return redirect()->route('news.index')->with('success', 'News updated successfully.');
    }

    public function destroy(Request $request, int $id)
    {
        $db = DB::connection('tenant');
        abort_unless($db->table('news_common_info')->where('news_id', $id)->exists(), 404);
        $portalId = (int) $request->attributes->get('portal')->id;
        $paths = $db->table('news_image_info')->where('news_id', $id)->pluck('img_path');

        $db->transaction(function () use ($db, $id) {
            $db->table('news_image_info')->where('news_id', $id)->delete();
            $db->table('news_tag_info')->where('news_id', $id)->delete();
            $db->table('news_writer_info')->where('news_id', $id)->delete();
            $db->table('news_seo_info')->where('news_id', $id)->delete();
            $db->table('news_reader_info')->where('news_id', $id)->delete();
            $db->table('common_details')->where('news_id', $id)->delete();
            $db->table('news_info')->where('news_id', $id)->delete();
            $db->table('news_common_info')->where('news_id', $id)->delete();
        });

        $base = realpath(public_path("images/news/{$portalId}"));
        if ($base) {
            foreach ($paths as $path) {
                $relative = ltrim(str_replace('\\', '/', (string) $path), '/');
                if (str_contains($relative, '..') || !str_starts_with($relative, "images/news/{$portalId}/")) continue;
                $real = realpath(public_path($relative));
                if ($real && str_starts_with($real, $base . DIRECTORY_SEPARATOR) && is_file($real)) File::delete($real);
            }
        }

        return redirect()->route('news.index')->with('success', 'News deleted successfully.');
    }

    private function sectionTexts(string $html): array
    {
        if (trim($html) === '') return [];
        $previous = libxml_use_internal_errors(true);
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->loadHTML('<?xml encoding="utf-8" ?><main>' . $html . '</main>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $texts = [];
        foreach ($doc->getElementsByTagName('div') as $section) {
            if (!str_contains(' ' . $section->getAttribute('class') . ' ', ' news-section ')) continue;
            $number = (int) $section->getAttribute('data-section');
            if ($number < 1) continue;
            $content = '';
            foreach (iterator_to_array($section->childNodes) as $child) {
                if ($child instanceof \DOMElement) {
                    $classes = ' ' . $child->getAttribute('class') . ' ';
                    if (str_contains($classes, ' news-media ') || str_contains($classes, ' news-video ') || str_contains($classes, ' news-video-caption ')) continue;
                }
                $content .= $doc->saveHTML($child);
            }
            $texts[$number] = $content;
        }
        if (!$texts) $texts[1] = $html;

        return $texts;
    }

    private function splitNames(string $value, int $limit, int $maxLength): array
    {
        $values = [];
        foreach (preg_split('/[,\n]+/u', $value) ?: [] as $item) {
            $item = trim(preg_replace('/\s+/u', ' ', $item));
            if ($item !== '' && mb_strlen($item) <= $maxLength) $values[mb_strtolower($item)] ??= $item;
        }

        return array_slice(array_values($values), 0, $limit);
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
                if (!$child instanceof \DOMElement) continue;
                if (!in_array(strtolower($child->tagName), $allowed, true)) {
                    $child->parentNode->replaceChild($doc->createTextNode($child->textContent), $child);
                    continue;
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
        };
        $root = $doc->getElementsByTagName('div')->item(0);
        $walk($root);
        $result = '';
        foreach ($root->childNodes as $child) $result .= $doc->saveHTML($child);

        return $result;
    }

    private function videoHtml(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);
        $id = null;
        if (in_array($host, ['youtu.be', 'www.youtu.be'], true)) $id = trim(parse_url($url, PHP_URL_PATH), '/');
        elseif (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true)) {
            parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $query);
            $id = $query['v'] ?? null;
            if (!$id && preg_match('~^/(?:shorts|embed)/([^/]+)~', parse_url($url, PHP_URL_PATH), $matches)) $id = $matches[1];
        }
        if ($id && preg_match('/^[A-Za-z0-9_-]{6,20}$/', $id)) return '<div class="news-video"><iframe src="https://www.youtube.com/embed/' . e($id) . '" allowfullscreen loading="lazy"></iframe></div>';

        return '<div class="news-video"><a href="' . e($url) . '" rel="nofollow noopener">' . e($url) . '</a></div>';
    }
}
