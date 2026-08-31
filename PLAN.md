# Media Library Picker Plan

## Goal

Build a reusable admin media library picker/library for image upload and selection. The first consuming surfaces are admin item forms and future Offer forms. The same vanilla JavaScript/custom CSS module must support two instantiation modes:

- **Media picker mode:** opens the media picker as a modal for selecting files into another form.
- **Media library mode:** renders the same file browser as an embedded component inside a Blade page by attaching it to a div element.

The backend must use the existing `plank/laravel-mediable` package tables: `media` and `mediables`.

The attached screenshots are visual references only. They are not instructions beyond the user's request to match the picker design exactly.

Reference images:

- `C:\Users\clemg\Downloads\stitch_vanilla_media_picker\media_library_picker\media_library_picker_screen.png`
- `C:\Users\clemg\Downloads\stitch_vanilla_media_picker\media_library_picker_sidebar_collapsed\media_library_picker_sidebar_collapsed_screen.png`
- `C:\Users\clemg\Downloads\stitch_vanilla_media_picker\media_library_picker_uploading_progress\media_library_picker_uploading_progress_screen.png`

## Current Repo State

- `plank/laravel-mediable` is installed in `composer.json`.
- `config/mediable.php` exists and uses the `public` disk by default with a 10 MB max size.
- The mediable migrations exist:
  - `database/migrations/2026_08_30_220306_create_mediable_tables.php`
  - `database/migrations/2026_08_30_220307_add_variants_to_media.php`
  - `database/migrations/2026_08_30_220308_add_alt_to_media.php`
- `items` currently has legacy `media_id` and `image_path` columns.
- `App\Models\Item` uses `Plank\Mediable\Mediable`.
- The admin item UI uses a media picker field instead of exposing an `Image Path` text field.
- No Offer model/table/admin surface exists in the files inspected, so Offer integration should be planned as the same reusable contract, not wired immediately.

## Design Contract

Implement the browser as a self-contained media surface whose styles are scoped with a prefix such as `.media-library-*`. The modal picker mode should visually follow the screenshots even though the broader admin CMS has a warmer forest/cream theme. The embedded library mode should use the same toolbar, file grid/list, details panel, and upload progress components, but render inside its host container instead of a dimmed popup.

Modal picker layout:

- Full-screen dim backdrop.
- Centered modal with small rounded corners, light cool background, and fixed desktop proportions matching the reference.
- Header toolbar with search on the left, centered category tabs, and action/view controls on the right.
- Grid-first asset area with square tiles, 1 px borders, selected cobalt outline, and a small checked corner marker.
- Optional right details panel with the tab row: Details, Activity, Permissions, Versions.
- Collapsed details state keeps the same modal shell but gives the grid the full width.
- Bottom upload progress dock appears over the modal bottom while uploads are active.

Embedded library layout:

- Renders inside a caller-provided div and fills the available component width.
- No full-screen backdrop, no modal centering wrapper, and no close button unless the host page opts into one.
- Keeps the search, tabs, upload button, grid/list toggle, details panel toggle, details tabs, file grid/list, empty state, and upload progress dock.
- Uses component-relative height options so a Blade page can render a fixed-height library panel or a naturally growing section.
- Selection can be enabled or disabled. A management-only library page can browse/upload/delete without an Insert button, while an embedded selection component can show Insert/Select.

Desktop visual targets:

- Modal around `calc(100vw - 64px)` wide, max around `1216px`.
- Main modal height around `693px` with details panel open, and around `597px` in the collapsed screenshot.
- Header height around `64px`.
- Details panel width around `319px`.
- Asset tiles around `158px` square with `16px` grid gaps.
- Primary action color from the mockup is the saturated indigo/cobalt, not the admin terracotta/forest action color.

Responsive behavior:

- At tablet/mobile widths, keep the modal as a full-screen dialog.
- Move the details panel into a slide-in drawer or below-selection panel so the grid remains usable.
- Keep upload progress pinned to the bottom of the picker.
- Preserve keyboard access for search, tabs, view toggles, close, upload, and insert.

Do not build this as a marketing page. The first consuming experience should be the popup picker opened from an image field, and the same module should also support rendering a full media library browser on a Blade page.

## Frontend Architecture

Create a framework-agnostic media library module:

```text
resources/js/plugins/media-library/media-library.js
resources/js/plugins/media-library/Media-library.css
resources/js/plugins/media-library/dist/media-library.min.js
resources/js/plugins/media-library/dist/media-library.min.css
```

Import the JS and CSS from the existing Vite entry points, but keep the media library code independent of Vue. Build one core renderer and two public instantiation APIs.

### Mode 1: Media Picker Modal

This mode mounts the shared browser into the modal shell and opens it for a consuming field:

```js
window.MediaLibraryPicker.open({
  selectedMediaId,
  accept: ['image'],
  multiple: false,
  tag: 'primary_image',
  onSelect(media) {
    // consuming form updates hidden media_id, preview, and label
  }
});
```

The Blade partial should render one reusable modal root near the end of the admin layout:

```blade
@include('admin.media-library._picker')
```

### Mode 2: Embedded Media Library

This mode attaches the shared browser to a div on any Blade page:

```html
<div
    id="admin-media-library"
    data-media-library
    data-media-library-mode="library"
    data-media-library-accept="image"
    data-media-library-selectable="false"
></div>
```

```js
window.MediaLibrary.mount('#admin-media-library', {
  mode: 'library',
  accept: ['image'],
  selectable: false,
  multiple: false,
  height: 'auto'
});
```

Embedded mode options:

- `mode: 'library'` renders inside the target element instead of opening a modal.
- `selectable: false` turns off Insert/Select and makes the component a media management browser.
- `selectable: true` keeps selection behavior for embedded chooser use cases.
- `height` supports fixed CSS lengths such as `680px`, `auto`, or `fill`.
- `initialType`, `initialQuery`, `detailsOpen`, and `view` let host pages choose their starting state.

The JS module owns:

- Core media state shared by both modes: loaded files, pagination, query, type filter, view mode, selection, active details tab, details panel state, upload queue, errors, loading, and empty state.
- Rendering the same toolbar, grid/list, details panel, and upload progress subcomponents in modal and embedded mode.
- Opening and closing the dialog in picker mode.
- Focus trap and Escape-to-close in picker mode only.
- Search input with `Ctrl+K` and `Cmd+K` focus.
- Category filters: All, Images, Documents, Audio, Video.
- Grid/list view toggle state.
- Details panel collapse/expand state.
- Selected media state.
- Insert action callback.
- Upload action using `XMLHttpRequest` so upload progress can drive the exact bottom progress bar.
- Optimistic insertion of newly uploaded media once the controller returns the created media JSON.

The modal picker should expose data attributes for simple Blade integration:

```html
<button
    type="button"
    data-media-picker-trigger
    data-media-picker-target-input="item_media_id"
    data-media-picker-preview="item_media_preview"
    data-media-picker-accept="image"
>
    Choose image
</button>
<input type="hidden" id="item_media_id" name="media_id">
```

## Backend Architecture

Add an admin controller dedicated to the picker:

```text
app/Http/Controllers/Admin/MediaLibraryController.php
app/Http/Requests/Admin/MediaLibrary/StoreMediaRequest.php
app/Http/Requests/Admin/MediaLibrary/AttachMediaRequest.php
app/Support/Media/MediaLibraryPresenter.php
```

Routes should live inside the existing authenticated admin route group in `routes/web.php`. Keep the Blade page route separate from the JSON listing route so embedded library mode can own `/admin/media-library` without content negotiation ambiguity:

```php
Route::get('/media-library', [MediaLibraryController::class, 'show'])->name('media-library.show');
Route::get('/media-library/files', [MediaLibraryController::class, 'index'])->name('media-library.index');
Route::post('/media-library/files', [MediaLibraryController::class, 'store'])->name('media-library.store');
Route::patch('/media-library/files/{media}', [MediaLibraryController::class, 'update'])->name('media-library.update');
Route::delete('/media-library/files/{media}', [MediaLibraryController::class, 'destroy'])->name('media-library.destroy');
Route::post('/media-library/attach', [MediaLibraryController::class, 'attach'])->name('media-library.attach');
```

Controller behavior:

- `show`: render `resources/views/admin/media/index.blade.php` with a div that boots embedded library mode.
- `index`: return paginated JSON for the picker, filterable by `query`, `type`, and optionally `selected`.
- `store`: validate uploaded files, upload through `MediaUploader`, and return the created media JSON.
- `update`: update editable metadata such as `alt`.
- `destroy`: delete only unattached media by default; reject deletion if rows exist in `mediables` unless a deliberate force-delete flow is later added.
- `attach`: allow attaching existing media to an allowlisted model/tag for already-created records. New unsaved forms should use hidden `media_id` fields and let their normal store/update controller sync after save.

Use package APIs already available locally:

```php
use Plank\Mediable\Facades\MediaUploader;

$media = MediaUploader::fromSource($request->file('file'))
    ->toDisk('public')
    ->toDirectory('media-library')
    ->onDuplicateIncrement()
    ->withAltAttribute($validated['alt'] ?? '')
    ->upload();
```

Presenter response shape:

```json
{
  "id": 1,
  "basename": "summer-beach-sunset.jpg",
  "filename": "summer-beach-sunset",
  "extension": "jpg",
  "mime_type": "image/jpeg",
  "aggregate_type": "image",
  "size": 1200000,
  "size_label": "1.2 MB",
  "width": 2000,
  "height": 1333,
  "dimensions_label": "2000 x 1333 px",
  "uploaded_at": "2026-08-30T12:00:00-04:00",
  "uploaded_label": "Aug 30, 2026",
  "url": "/storage/media-library/summer-beach-sunset.jpg",
  "preview_url": "/storage/media-library/summer-beach-sunset.jpg",
  "alt": "",
  "attached_count": 0
}
```

Dimensions can be read with `getimagesize($media->getAbsolutePath())` for images. Do not fabricate color profile data; show `sRGB` only if the implementation can detect it reliably, otherwise show `Unknown` or omit the row.

## Mediable Integration

Add `Mediable` to models that can own uploaded media. For the first concrete integration:

```php
use Plank\Mediable\Mediable;

class Item extends Model
{
    use HasFactory;
    use Mediable;
}
```

Use tags instead of relying on the legacy `items.media_id` column:

- `primary_image` for the main item image.
- Future tags can include `gallery`, `thumbnail`, or `offer_banner`.

Item controller integration:

- Validate `media_id` as `nullable|exists:media,id`.
- On create/update, after the `Item` is saved, call:

```php
if ($request->filled('media_id')) {
    $item->syncMedia((int) $request->input('media_id'), 'primary_image');
} else {
    $item->detachMediaTags('primary_image');
}
```

Compatibility path:

- Keep `image_path` readable during the first migration so existing data does not disappear.
- Prefer `firstMedia('primary_image')` when present.
- Later, backfill existing `image_path` files into the media table if those files exist on disk, then retire the text field.
- Do not make admin CMS pages depend on bearer tokens; this is a session-auth admin feature.

Future Offer integration:

- The Offer model should use `Mediable`.
- The Offer form should use the same hidden `media_id` contract and a tag such as `primary_image` or `offer_banner`.
- If an Offer needs multiple images, call the picker with `multiple: true` and sync ordered media IDs to the chosen tag.

## Security And Validation

First version should allow image selection/upload because the stated consumers are item and Offer images.

Recommended upload validation:

```php
'file' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,gif,webp,heic', 'max:10240'],
'alt' => ['nullable', 'string', 'max:500'],
```

Config follow-up:

- Add `webp` to the mediable image aggregate config if webp support is required.
- Keep `php`, `phtml`, and other executable extensions forbidden.
- Store on the public disk in `media-library`.
- Use CSRF protection on every mutating route.
- Restrict all routes to the existing `auth` + `admin` middleware.
- Reject generic attach requests unless the `mediable_type` is in an explicit allowlist such as `Item::class` and future `Offer::class`.

## Implementation Phases

### Phase 1: Backend JSON Media Library

- Add `MediaLibraryController`.
- Add request classes for upload and attach.
- Add `MediaLibraryPresenter`.
- Add admin media-library routes for both the embedded Blade page and JSON file operations.
- Add an optional Media Library nav item if the embedded page should be globally reachable from the admin sidebar.
- Return paginated media JSON with filters for search and aggregate type.
- Ensure upload creates a `media` row and stores the file on the public disk.
- Ensure delete removes the file and media row only when unattached.

### Phase 2: Shared Media Browser UI

- Add the Blade partial for the modal shell.
- Add the Blade page for embedded library mode.
- Add scoped custom CSS that reproduces the screenshots for modal mode and adapts the same browser components for embedded mode.
- Add vanilla JS module and expose both `window.MediaLibrary.mount(...)` and `window.MediaLibraryPicker.open(...)`.
- Implement a shared renderer for grid view, collapsed details panel, details tabs, search, type filters, selection, insert/select controls, close, and upload progress.
- Use inline SVG icons or a small local icon helper so the picker has no runtime icon dependency.

### Phase 2A: Modal Picker Instantiation

- Mount the shared browser into `resources/views/admin/media-library/_picker.blade.php`.
- Keep modal-only behavior isolated: backdrop, focus trap, Escape close, close button, and Insert callback.
- Wire data-attribute triggers so any Blade form can open the picker and receive the selected media ID.

### Phase 2B: Embedded Library Instantiation

- Mount the shared browser into a caller-provided div on `resources/views/admin/media/index.blade.php`.
- Support auto-mounting any element with `data-media-library`.
- Keep management behavior available: browse, search, filter, upload, inspect details, update alt text, and delete unattached media.
- Allow embedded selection mode by passing `data-media-library-selectable="true"` or `selectable: true`.

### Phase 3: Item Form Integration

- Add `Mediable` to `Item`.
- Load `withMedia('primary_image')` for the item index/edit payload.
- Replace the visible `Image Path` text input with a picker trigger, thumbnail preview, filename label, clear button, and hidden `media_id`.
- Keep `image_path` fallback display for legacy rows.
- On item create/update, sync or detach the `primary_image` tag.

### Phase 4: Future Model Contract

- Document how future tables add the picker:
  - add `Mediable` to the model;
  - add hidden `media_id` or `media_ids[]` fields;
  - validate IDs;
  - sync to an agreed tag;
  - load `withMedia($tag)` where the admin list/form needs previews.
- Use the same contract for the future Offers table when it is added.

### Phase 5: Tests And Verification

Backend feature tests:

- Non-admin users cannot access media-library routes.
- Admin can list media.
- Search filters by filename.
- Type filter returns only image/document/audio/video aggregate types as requested.
- Admin can upload an image and gets media JSON with URL, size, dimensions, and uploaded date.
- Upload rejects invalid MIME types and files over the size limit.
- Admin can update alt text.
- Delete rejects attached media.
- Delete removes unattached media and the stored file.
- Item create with `media_id` writes a `mediables` row tagged `primary_image`.
- Item update replaces the previous `primary_image`.
- Item update with no `media_id` detaches the previous `primary_image`.
- Existing item create/update behavior for options, active state, and validation still passes.

Frontend/manual verification:

- Run `npm run build`.
- Run the relevant PHPUnit feature tests.
- Open the item create/edit modal and verify the picker against the three attached screenshots.
- Open `/admin/media-library` and verify the same browser renders embedded in the Blade page without the modal backdrop or close behavior.
- Check desktop with details panel open, desktop with details panel collapsed, and upload progress state.
- Check mobile sizing, keyboard focus, Escape close, and Insert behavior.

## Open Questions

- Should the first version expose non-image tabs as working filters, or should they be visually present but disabled until non-image media is needed?
- Should uploaded images generate real thumbnail variants now, or should the first version use original image URLs and add variants after the picker is working?
- Should deleting a media file be blocked forever once attached anywhere, or should admins get a force-delete flow later?
- Should existing `items.image_path` values be backfilled immediately, or kept as fallback until a separate cleanup task?
- What is the exact tag name preferred for Offers: `primary_image`, `offer_banner`, or both?
