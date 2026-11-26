# Ad image example

Put one or more `.jpg`, `.jpeg`, `.png`, or `.webp` ad creatives in `public/ads/`. When `ad=1` is requested, the endpoint keeps the requested original image at the top and appends a same-width 16:10 panel directly underneath. The full ad stays sharp and keeps its original aspect ratio in the center, over a blurred, light-gray-tinted cover of that ad that fills the panel. This avoids distorting or cropping wide banners. The combined result is cached as an image response; the upload and database record remain unchanged.

For example, a 500×500 original gets an ad area about 500×313 pixels underneath, for a final image around 500×813.

Use the same uploaded image to compare the normal and composed responses:

```text
http://127.0.0.1:8000/image/uploads/portal-categories/5/3WKhdoHpk2XGuUESxl7zSBLWEdp40nQZ1iUNlVI3.webp?size=500
http://127.0.0.1:8000/image/uploads/portal-categories/5/3WKhdoHpk2XGuUESxl7zSBLWEdp40nQZ1iUNlVI3.webp?size=500&ad=1
http://127.0.0.1:8000/image/uploads/portal-categories/5/3WKhdoHpk2XGuUESxl7zSBLWEdp40nQZ1iUNlVI3.webp?ad=1&size=500
```
