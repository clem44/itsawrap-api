# Hero Slider implementation plan

The mockup maps to a three-table model: a slider, its ordered slides, and up to two buttons per slide. Build the editor as a vanilla JavaScript plugin mounted in a new admin Blade view. The plugin receives data and callbacks through configuration so its core can move to another project without Laravel dependencies.

## Database schema

| Table | Main columns | Purpose |
| --- | --- | --- |
| `sliders` | `id`, `name`, `slug` (unique), `is_active`, `autoplay`, `autoplay_interval_ms`, timestamps | One reusable carousel configuration. Seed `homepage-main` for this site. |
| `slides` | `id`, `slider_id`, `sort_order`, `is_active`, `badge`, `heading`, `body`, `text_alignment`, `overlay_color`, `overlay_opacity`, `transition`, `transition_ms`, timestamps | Ordered slide content and appearance. |
| `slide_buttons` | `id`, `slide_id`, `sort_order`, `label`, `url`, `style`, timestamps | Zero to two calls to action per slide; `style` is `solid` or `outline`. |

Use the existing `mediables` relationship to attach one image to each slide with a `hero_image` tag. That keeps the schema small and lets the media library's existing deletion protection recognize the attachment. Return the media ID, URL, alt text, and dimensions in the API response rather than copying those values into `slides`.

Keep the mockup's **Desktop 16:9 preset** as a preview setting initially. If different aspect ratios become published slider configurations, add that field to `sliders` then.

## Plugin contract and admin view

Create a framework independent module under `resources/js/plugins/hero-slider/` with its own CSS. Its public interface should be close to:

```js
HeroSliderBuilder.mount(element, {
  slider,
  openMediaPicker: ({ selectedMediaId, onSelect }) => {},
  save: async (sliderDraft) => {},
});
```

The new `resources/views/admin/hero-sliders/` Blade view supplies initial data, URLs, CSRF handling, and the media adapter. The adapter calls the existing `window.MediaLibraryPicker.open(...)`; the plugin itself only consumes the selected media object. Provide `destroy()` for clean remounting. Keep the plugin's DOM and CSS scoped to its mount element.

Build the mockup's stage preview, slide deck, configuration panel, add/remove/reorder controls, buttons, and preview mode. "Apply to Slide" updates the local draft and stage; "Save Changes" persists the whole slider. Show unsaved changes and server validation errors. On narrow screens, stack the stage and editor and offer reorder controls alongside drag and drop. Wire the existing desktop **Hero Slider** placeholder link and add the mobile navigation entry.

## Laravel persistence and API

Use an admin session route to load and save the builder. Validate a complete slider payload in a Form Request and save slider settings, slide order, media attachments, and buttons in one transaction. Enforce the two-button limit, unique slide positions, valid image media, bounded opacity and durations, and safe button URLs on the server.

Add a public, read-only endpoint:

`GET /api/guest/sliders/{slug}`

It returns **only active sliders and active slides**, sorted by `sort_order`, with buttons sorted likewise. An inactive or unknown slider returns 404. The response should include slider playback settings and each slide's content, image metadata, overlay, alignment, transition, and buttons. This gives other clients the same published slide data without exposing admin drafts. If POS clients also need draft management later, add authenticated API writes separately; the CMS's session routes remain the initial editing path.

Add OpenAPI attributes for the endpoint and regenerate Swagger docs. There is currently no active public hero Blade view in this repository—the root route redirects to admin—so the first Blade mount is the **admin builder**. A public site can later use the same API response with a small vanilla JS playback renderer.

## Delivery order and checks

1. Add migrations, Eloquent relationships, and a seed for `homepage-main`.
2. Add the public API resource and feature tests for active filtering, order, media, buttons, and 404 behavior.
3. Add transactional admin save with feature tests for validation, reordering, media attachment, and button limits.
4. Build the standalone plugin and mount it in the Blade admin page; connect the media picker.
5. Check desktop and mobile layouts, keyboard controls, autoplay pause behavior, reduced motion, empty states, and save errors.
6. Run focused Laravel tests, `composer test`, and `npm run build`.

This plan treats the attached image as a layout reference. Its sample slide thumbnails and stage content differ, so actual slide data will come from the database rather than from the mockup.
