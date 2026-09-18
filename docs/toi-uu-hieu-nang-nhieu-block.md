# Tối ưu hiệu năng khi plugin có hàng trăm block

> Câu hỏi gốc: *"plugin có hàng trăm block thì có làm nặng site người dùng không?"*
>
> Trả lời ngắn: **CSS/JS front-end không phình theo số block** — trang của khách chỉ tải asset của
> đúng những block có mặt trên trang. Nhưng **PHP thì có**: việc đăng ký block chạy trên mọi request
> và tốn ~18 ms cho 76 block. Cộng với **màn hình editor** (8 MB JS + 1 MB CSS), đó là bốn nút thắt
> tài liệu này chỉ ra và cách sửa từng cái.
>
> Số liệu đo ngày 2026-09-04; **bổ sung mục 3.4 + 5.7 ngày 2026-09-09** sau khi đo chi phí đăng ký block.

---

## 1. Hiện trạng đo được

| Chỉ số | Giá trị |
|---|---|
| Số thư mục block (`src/blocks/`) | **76** |
| Số entry trong `Block_Manager::BASE_BLOCKS` | 83 (gồm block con) |
| Thư mục `build/` | **14 MB** |
| Tổng JS editor (`build/blocks/*/index.js`) | **8.0 MB** — 76 file, trung bình **108 KB**, nhỏ nhất 39 KB |
| Số bundle editor chứa cùng code `@components` | **63 / 76** |
| Tổng CSS editor (`build/blocks/*/index.css`) | **1.03 MB** — 76 file, nhỏ nhất 13.4 KB |
| Số file CSS editor chứa cùng `components/editor.scss` | **76 / 76** |
| Tổng CSS front-end (`style-index.css`) | **118 KB** — 73 file, TB **1.6 KB**, nạp có điều kiện |
| Admin app (chỉ trang cài đặt plugin) | JS 55 KB + CSS 11 KB |
| Số file generator PHP nạp mỗi request | **63 file / 14.119 dòng** |
| Tổng PHP trong `includes/` | 23.098 dòng |
| Đọc + parse 76 `block.json` mỗi request | **18.34 ms** (ước tính 200 block: **48 ms**) |
| JS front-end dùng chung | `animation.js` **0.5 KB**, `inline-editor.js` **3.1 KB** |
| `view.js` nặng nhất | `lottie` **306 KB**, `slides` **95 KB**, còn lại đều < 5 KB |

Lệnh đo lại (chạy sau `npm run build`):

```bash
# tổng JS editor + trung bình
find build/blocks -name "index.js" -printf "%s\n" \
  | awk '{s+=$1} END {printf "%.1f MB / %d file / TB %.0f KB\n", s/1048576, NR, s/NR/1024}'

# top bundle nặng
find build/blocks -name "index.js" -printf "%s %p\n" | sort -rn | head -10 \
  | awk '{printf "%6.1f KB  %s\n", $1/1024, $2}'

# bao nhiêu bundle đang lận cùng một khối code dùng chung
grep -lc "flexa-dualcolor" build/blocks/*/index.js | wc -l

# CSS editor: tổng + số file lặp design-system của panel
find build/blocks -name "index.css" -printf "%s\n" \
  | awk '{s+=$1} END {printf "%.0f KB / %d file\n", s/1024, NR}'
grep -lc "flexa-inspector" build/blocks/*/index.css | wc -l
```

---

## 2. Front-end: KHÔNG nặng theo số block — và đây là lý do

Ba cơ chế cộng lại khiến chi phí front-end tỉ lệ với **số block có trên trang**, không phải số block
plugin cài đặt.

**a) CSS sinh lúc SAVE, không sinh lúc render.**
[`CSS_Generator_Service::generate_for_post()`](../includes/class-css-generator-service.php) `parse_blocks()`
nội dung bài **một lần lúc lưu**, ghi kết quả vào post meta. Lúc render,
[`Asset_Loader::output_page_css()`](../includes/class-asset-loader.php) chỉ `get_post_meta()` rồi
`wp_add_inline_style()`. Không parse block, không chạy generator trên request của khách.
→ Thêm 100 generator cũng không tốn thêm mili-giây nào ở front-end.

**b) CSS/JS riêng của block chỉ nạp khi block xuất hiện.**
Đây là hành vi của WordPress core, không phải thứ ta phải tự làm:
[`WP_Block::render()`](../../../../wp-includes/class-wp-block.php#L626) chỉ gọi `wp_enqueue_style()`
cho `style_handles` và `wp_enqueue_script()` cho `view_script_handles` **khi block thật sự được
render**. Trang chỉ có Heading thì `style-index.css` của 75 block kia không hề được nạp.

**c) Block WooCommerce không đăng ký khi thiếu WooCommerce.**
[`Block_Manager::register_blocks()`](../includes/class-block-manager.php#L765) bỏ qua entry `is_woo`
khi `class_exists('WooCommerce')` sai — 15 block `product-*` biến mất hoàn toàn khỏi site không bán hàng.

**d) CSS front-end vốn đã rất nhẹ.** Tổng `style-index.css` của cả 73 block chỉ **118 KB**, trung
bình **1.6 KB**/block, và chỉ nạp khi block xuất hiện (mục b). Nặng nhất là `post-filter` 7.2 KB.
Không có gì phải tối ưu ở đây.

> **Kết luận mục này:** với người dùng cuối, một trang landing dùng 8 block sẽ nạp đúng CSS/JS của 8
> block đó cộng ~0.5 KB observer animation. Con số 76 hay 176 block không xuất hiện ở đâu trong
> waterfall của họ.
>
> ⚠️ **Nhưng chỉ đúng cho phần asset.** Chi phí **PHP** để đăng ký block thì vẫn tỉ lệ với tổng số
> block và chạy trên mọi request — xem mục 3.4.

---

## 3. Bốn nút thắt thật sự

### 3.1. ⚠️ Nút thắt #1 — Tài sản editor: 8 MB JS + 1 MB CSS, tăng tuyến tính theo số block

WordPress nạp `editorScript` của **mọi block đã đăng ký** khi mở bất kỳ màn hình editor nào. Không có
cơ chế lazy-load per-block. Nghĩa là hôm nay tác giả nội dung mở một bài viết là tải **8 MB JS**, và
mỗi block mới cộng thêm trung bình 108 KB.

Nguyên nhân không phải block quá to, mà là **trùng lặp**: bundle nhỏ nhất
(`process-bar`) đã 39 KB, và 63/76 bundle chứa cùng khối code `@components` + `@utils`. Ước tính
**~3 MB trong 8 MB là cùng một đoạn code lặp 63 lần**, vì `webpack.config.js` hiện **không cấu hình
`splitChunks`** — mỗi entry tự gói lấy toàn bộ phụ thuộc của nó.

**CSS editor dính đúng cùng một bệnh, thậm chí nặng hơn về tỉ lệ.** `build/blocks/*/index.css` tổng
**1.03 MB**, và **76/76 file** đều chứa `flexa-inspector` — tức toàn bộ design-system của Inspector
(`src/components/editor.scss`) bị nhúng lại vào từng block. File nhỏ nhất đã 13.4 KB, mà 13.4 KB × 76
≈ 1 MB: **gần như 100% CSS editor là bản sao của cùng một stylesheet.**

Đây là vấn đề sẽ trở nên nghiêm trọng ở mốc "hàng trăm block":
200 block × 108 KB ≈ **21 MB JS** + ~**2.7 MB CSS** mỗi lần mở editor.

### 3.2. Nút thắt #2 — 63 file generator PHP nạp trên MỌI request

[`flexa-block.php:47`](../flexa-block.php#L47) require `includes/css-generators/index.php`, file này
`glob()` rồi `require_once` **cả 63 file generator (14.119 dòng)** — trên mọi request, kể cả request
front-end không có block Flexa nào, kể cả REST/AJAX/cron.

Nhưng generator **chỉ được dùng lúc save bài** (mục 2a). Front-end không bao giờ gọi tới chúng.
Với opcache bật thì chi phí là compile-một-lần + link class mỗi request; không thảm hoạ, nhưng là
14 nghìn dòng vô ích và nó **tăng tuyến tính theo số block**.

### 3.3. Nút thắt #3 — thư viện bên thứ ba nằm trong bundle

`lottie` là ngoại lệ lớn: **378 KB editor + 306 KB view**. `slides` (Swiper) 95 KB view. Cả hai đều
là thư viện đóng gói cứng vào bundle. Với `view.js` thì còn chấp nhận được (chỉ nạp khi block có mặt
— mục 2b), nhưng 378 KB trong bundle editor thì bị nạp **mọi lần mở editor**, kể cả bài không dùng
Lottie.

### 3.4. ⚠️ Nút thắt #4 — Đăng ký block: đọc + parse N file `block.json` trên MỌI request

> Mục này được thêm sau khi đo lại ngày 2026-09-09. Nó **sửa lại** khẳng định "front-end miễn nhiễm
> hoàn toàn với số block" ở bản đầu: front-end miễn nhiễm về **asset**, nhưng **không** miễn nhiễm về
> **PHP đăng ký block**.

[`Block_Manager::init()`](../includes/class-block-manager.php) móc `register_blocks()` vào `init`
priority 5 — tức chạy trên **mọi** request, front-end lẫn admin lẫn REST. Mỗi block gọi
`register_block_type( $path )`, và [`register_block_type_from_metadata()`](../../../../wp-includes/blocks.php#L480)
sẽ `wp_json_file_decode()` **một file `block.json` cho từng block**, trừ khi block đó nằm trong một
*metadata collection* đã đăng ký trước.

Plugin hiện **không** dùng collection, nên chi phí là tuyến tính. Đo thật trên máy dev:

```
76 block.json | đọc + decode 1 lượt: 18.34 ms | ước tính 200 block: 48.27 ms
```

Con số này **chỉ là phần JSON**; chưa tính chi phí dựng 76 object `WP_Block_Type` và
`wp_register_script()`/`wp_register_style()` 2–3 handle mỗi block. Trên host Linux có page cache tốt
thì sẽ nhanh hơn máy Windows dev, nhưng bản chất tuyến tính không đổi — và opcache **không** giúp gì,
vì nó cache PHP chứ không cache JSON.

Lệnh đo lại:

```bash
php -r '$f=glob("build/blocks/*/block.json");$t=microtime(true);
for($i=0;$i<20;$i++){foreach($f as $x){json_decode(file_get_contents($x),true);}}
printf("%d file | %.2f ms/luot\n",count($f),(microtime(true)-$t)*1000/20);'
```

---

## 4. Việc cần làm, xếp theo ROI

### Ưu tiên 1 — Tách chunk dùng chung cho editor *(giảm ước tính ~3 MB JS + ~1 MB CSS)*

Cấu hình `splitChunks` trong `webpack.config.js` để `@components` + `@utils` thành **một** chunk
`build/shared/editor.js` — và vì `MiniCssExtractPlugin` bám theo chunk, `components/editor.scss`
cũng tự gom thành **một** `build/shared/editor.css` thay vì 76 bản sao. Rồi:

1. `wp_register_script( 'flexa-block-editor-shared', … )` trong `Asset_Loader`.
2. Thêm handle đó vào `editor_script_handles` (hoặc vào deps) của từng block sau khi
   `register_block_type()` trả về — chỗ móc sẵn có là vòng lặp trong `Block_Manager::register_blocks()`.

Cạm bẫy phải kiểm: `DependencyExtractionWebpackPlugin` của `@wordpress/scripts` sinh `index.asset.php`
theo từng entry; chunk chung phải có `asset.php` riêng và thứ tự enqueue phải đảm bảo chunk chung
chạy **trước** mọi `index.js` của block.

**Đây là việc bắt buộc trước khi vượt mốc ~100 block.**

### Ưu tiên 2 — Nạp generator PHP theo yêu cầu

Bỏ `glob()` nạp-hết. Thay bằng map `class => file` (có thể sinh lúc build) và chỉ `require_once`
đúng generator cần dùng, ngay trong `CSS_Generator_Service::process_blocks()` khi gặp block đó.
Front-end sẽ không nạp file generator nào.

Kèm theo: block nằm trong `disabled_blocks` thì cũng **không nạp generator** của nó. Hiện
`disabled_blocks` mới chỉ chặn `register_block_type` qua filter `flexa_block_registerable_blocks`
([class-admin.php:72](../includes/admin/class-admin.php#L72)), chưa chặn phần PHP.

### Ưu tiên 3 — Đưa Lottie ra khỏi bundle editor

Dùng `import()` động cho `lottie-web` trong `view.ts`, và ở `edit.tsx` thay preview bằng ảnh/skeleton
tĩnh thay vì nạp cả thư viện. Riêng việc này cắt 378 KB khỏi mọi lần mở editor.

### Ưu tiên 4 — Ngân sách kích thước trong CI

Thêm một script kiểu `npm run check:size` chạy cùng `composer check`, fail khi:

- tổng `build/blocks/*/index.js` vượt ngưỡng đã chốt, hoặc
- một `index.js` đơn lẻ vượt (ví dụ) 150 KB.

Không có cổng chặn thì con số 8 MB sẽ âm thầm thành 20 MB, đúng như cách nó đã âm thầm thành 8 MB.

### Ưu tiên 5 — Cho người dùng tắt bớt block

Cơ chế đã có (`disabled_blocks`). Cái còn thiếu là **hướng dẫn**: nói rõ trong tài liệu người dùng
rằng tắt nhóm block không dùng sẽ làm editor nhẹ hơn — và sau Ưu tiên 2 thì tắt block cũng giảm cả
chi phí PHP.

---

## 5. Công thức khắc phục chi tiết

> Mục 4 là "làm gì". Mục này là "làm thế nào" — code cụ thể cho đúng codebase này.
>
> ⚠️ Các đoạn dưới đây **chưa được chạy thử**; chúng bám sát cấu trúc file hiện tại nhưng phải
> build + kiểm tra thật trước khi commit. Mỗi công thức có kèm phần "cách kiểm chứng".

### 5.1. Gộp chunk dùng chung cho editor

Vấn đề gốc: `webpack.config.js` hiện không khai `optimization`, nên mỗi entry tự gói `@components` +
`@utils` + `components/editor.scss` vào bundle của nó.

**Cạm bẫy phải biết trước:** mỗi entry webpack có **runtime riêng** với registry module riêng. Nếu chỉ
thêm `splitChunks` mà không gộp runtime, block A **không** dùng được chunk mà block B đã nạp — phải
có `runtimeChunk` chung.

#### Cách A — splitChunks + runtime chung (ít code, nhưng ràng buộc thứ tự nạp)

```js
// webpack.config.js — thêm vào module.exports
optimization: {
    ...defaultConfig.optimization,
    runtimeChunk: { name: 'shared/runtime' },
    splitChunks: {
        cacheGroups: {
            flexaShared: {
                test: /[\\/]src[\\/](components|utils|shared|hooks)[\\/]/,
                name: 'shared/editor',
                chunks: 'all',
                minChunks: 2,
                enforce: true,
            },
        },
    },
},
```

Sinh ra `build/shared/runtime.js`, `build/shared/editor.js` và — nhờ `MiniCssExtractPlugin` bám theo
chunk — `build/shared/editor.css`.

Phía PHP, đăng ký handle rồi **chèn vào deps của từng block**:

```php
// includes/class-asset-loader.php
public static function register_shared_editor_assets(): void {
    $ver = FLEXA_BLOCK_VER;
    wp_register_script( 'flexa-block-runtime', FLEXA_BLOCK_URL . 'build/shared/runtime.js', [], $ver, true );
    wp_register_script(
        'flexa-block-editor-shared',
        FLEXA_BLOCK_URL . 'build/shared/editor.js',
        [ 'flexa-block-runtime', 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-i18n', 'wp-data' ],
        $ver,
        true
    );
    wp_register_style( 'flexa-block-editor-shared', FLEXA_BLOCK_URL . 'build/shared/editor.css', [], $ver );
}
```

```php
// includes/class-block-manager.php — trong register_blocks(), ngay sau khi $result trả về
if ( $result ) {
    // Chunk chung phải chạy TRƯỚC index.js của block, nếu không runtime chưa có module.
    $scripts = wp_scripts();
    foreach ( (array) $result->editor_script_handles as $handle ) {
        if ( isset( $scripts->registered[ $handle ] ) ) {
            $scripts->registered[ $handle ]->deps[] = 'flexa-block-editor-shared';
        }
    }
    $styles = wp_styles();
    foreach ( (array) $result->editor_style_handles as $handle ) {
        if ( isset( $styles->registered[ $handle ] ) ) {
            $styles->registered[ $handle ]->deps[] = 'flexa-block-editor-shared';
        }
    }
}
```

#### Cách B — biến `@components`/`@utils` thành "package" kiểu WordPress *(bền hơn, nên chọn nếu định lên hàng trăm block)*

Đúng cách WordPress làm với `wp-components`: build một lần ra biến global, rồi khai `externals` để
block **không bundle** nữa.

```js
// webpack.config.js
entry: {
    'shared/editor': path.resolve( process.cwd(), 'src/shared-entry.ts' ),
    ...blockEntries, ...adminEntry, ...frontendEntry,
},
externals: {
    ...( defaultConfig.externals || {} ),
    '@components': [ 'flexaBlock', 'components' ],
    '@utils':      [ 'flexaBlock', 'utils' ],
},
```

```ts
// src/shared-entry.ts
import * as components from './components';
import * as utils from './utils';

window.flexaBlock = { ...( window.flexaBlock || {} ), components, utils };
```

Không cần `runtimeChunk`, không ràng buộc runtime giữa các entry — chỉ cần handle
`flexa-block-editor-shared` nạp trước, y hệt cách `wp-components` hoạt động. Vẫn phải chèn dep như
Cách A.

**Đánh đổi:** Cách A nhanh hơn để thử (chỉ sửa config), Cách B tốn thêm một entry và một khai báo
type cho `window.flexaBlock` trong `global.d.ts`, nhưng không có rủi ro runtime-mismatch — thứ rất
khó debug khi có hàng trăm entry.

**Cách kiểm chứng (bắt buộc, cho cả hai cách):**

```bash
npm run build
# tổng phải giảm mạnh so với 8.0 MB / 1.03 MB
find build/blocks -name "index.js"  -printf "%s\n" | awk '{s+=$1} END {printf "JS  %.1f MB\n", s/1048576}'
find build/blocks -name "index.css" -printf "%s\n" | awk '{s+=$1} END {printf "CSS %.0f KB\n", s/1024}'
# code dùng chung không còn lặp trong từng block
grep -lc "flexa-dualcolor" build/blocks/*/index.js | wc -l   # kỳ vọng: 0
```

Rồi mở editor và chèn thử **một block mỗi nhóm** (container, post-grid, product-add-to-cart, lottie,
subscribe-form). Console báo `Cannot read properties of undefined` nghĩa là thứ tự nạp sai.

### 5.2. Nạp generator PHP theo yêu cầu — dùng autoloader

Tên class ↔ tên file trong `includes/css-generators/` là ánh xạ **xác định**:
`Product_Add_To_Cart_CSS` → `class-product-add-to-cart-css.php`. Nên chỉ cần một autoloader, không
phải sinh map lúc build.

```php
// flexa-block.php — THAY cho dòng:
//   require_once FLEXA_BLOCK_DIR . 'includes/css-generators/index.php';
spl_autoload_register( static function ( $class ) {
    $prefix = 'Flexa\\Block\\CSS_Generators\\';
    if ( 0 !== strncmp( $class, $prefix, strlen( $prefix ) ) ) {
        return;
    }
    $short = substr( $class, strlen( $prefix ) );                  // Product_Add_To_Cart_CSS
    $file  = 'class-' . str_replace( '_', '-', strtolower( $short ) ) . '.php';
    $path  = FLEXA_BLOCK_DIR . 'includes/css-generators/' . $file;
    if ( is_readable( $path ) ) {
        require_once $path;
    }
} );
```

Không phải sửa gì thêm: [`CSS_Generator_Service::process_blocks()`](../includes/class-css-generator-service.php)
đã gọi `class_exists( $class )` trước khi dùng, và `class_exists()` tự kích hoạt autoloader. Đã kiểm:
ngoài chuỗi tên class trong `BASE_BLOCKS`, không chỗ nào khác trong `includes/` gọi thẳng các class này.

Kết quả: request front-end nạp **0 file generator** thay vì 63 file / 14.119 dòng.

**Lưu ý add-on:** generator của add-on nằm ngoài namespace/thư mục này nên vẫn phải tự `require_once`
như guide §5 hướng dẫn — autoloader này không đụng tới chúng.

**Cách kiểm chứng:**

```bash
php vendor/bin/phpunit           # tests/test-init.php require tường minh nên vẫn phải xanh
grep -rn "css-generators/index.php" --include=*.php . | grep -v build   # kỳ vọng: không còn
```

Thêm một test nhỏ để khỏi đi lùi: khẳng định một class generator **chưa** được nạp
(`class_exists( …, false )` với tham số thứ hai `false` để không kích hoạt autoloader) trước khi có
ai gọi tới nó.

### 5.3. Đưa Lottie ra khỏi bundle editor

[`src/blocks/lottie/edit.tsx:16`](../src/blocks/lottie/edit.tsx#L16) đang `import lottie from 'lottie-web'`
**tĩnh**, dù comment ở dòng 271 nói là "lazy-load". Chính dòng import đó kéo 378 KB vào bundle editor
và bị nạp mỗi lần mở editor, kể cả khi bài không có Lottie.

```ts
// edit.tsx — bỏ import tĩnh ở đầu file, chuyển vào đúng chỗ cần chạy preview
const startPreview = async () => {
    const { default: lottie } = await import( /* webpackChunkName: "lottie" */ 'lottie-web' );
    lottie.loadAnimation( { /* … */ } );
};
```

Làm tương tự trong `view.ts` để `lottie-web` thành chunk tách rời, chỉ tải khi block thực sự có trên
trang.

**Cách kiểm chứng:** `index.js` của lottie phải tụt từ 378 KB xuống vài chục KB và xuất hiện một chunk
`lottie.*.js` riêng; mở editor một bài **không** có Lottie thì tab Network không được tải chunk đó.

### 5.4. Ngân sách kích thước trong CI

```js
// scripts/check-size.mjs
import { readdirSync, statSync } from 'node:fs';
import { join } from 'node:path';

const LIMITS = { totalEditorMB: 4, singleBundleKB: 150 };   // siết dần sau mỗi lần tối ưu
const dir = 'build/blocks';
let total = 0;
const offenders = [];

for ( const slug of readdirSync( dir ) ) {
    let size = 0;
    try {
        size = statSync( join( dir, slug, 'index.js' ) ).size;
    } catch {
        continue;
    }
    total += size;
    if ( size / 1024 > LIMITS.singleBundleKB ) {
        offenders.push( `${ slug }: ${ ( size / 1024 ).toFixed( 0 ) } KB` );
    }
}

const totalMB = total / 1048576;
if ( totalMB > LIMITS.totalEditorMB || offenders.length ) {
    console.error( `✗ vượt ngân sách: tổng ${ totalMB.toFixed( 1 ) } MB (trần ${ LIMITS.totalEditorMB } MB)` );
    offenders.forEach( ( o ) => console.error( `  - ${ o }` ) );
    process.exit( 1 );
}
console.log( `✓ kích thước editor: ${ totalMB.toFixed( 1 ) } MB` );
```

```jsonc
// package.json
"check:size": "node scripts/check-size.mjs",
"check": "npm run lint:reuse && npm run check:size && npm run typecheck && npm run test"
```

Đặt trần **thấp hơn con số thực tế một chút ngay sau khi tối ưu xong**, để lần phình tiếp theo bị chặn
chứ không lại âm thầm trôi từ 8 MB lên 20 MB như vừa rồi.

### 5.5. `disabled_blocks` và phía server

[`class-admin.php:72`](../includes/admin/class-admin.php#L72) hiện lọc `flexa_block_registerable_blocks`,
nên block bị tắt không đăng ký → không tốn JS/CSS editor. Đó đã là phần tiết kiệm lớn nhất.

Sau 5.2 thì phía PHP cũng tự đúng: block bị tắt không có generator nào được autoload, **trừ khi** nội
dung cũ vẫn còn block đó — lúc ấy autoloader nạp đúng một file cần thiết. Đây là hành vi mong muốn:
tắt block không được làm hỏng CSS của bài đã xuất bản.

### 5.6. Gộp toàn bộ `block.json` thành một manifest *(bắt buộc trước mốc ~150 block)*

WordPress 6.7 thêm [`wp_register_block_metadata_collection()`](../../../../wp-includes/blocks.php#L436)
+ `WP_Block_Metadata_Registry`: khai báo **một** file PHP chứa metadata của tất cả block, thì
`register_block_type()` đọc từ đó thay vì mở từng `block.json`. N lần đọc file + `json_decode`
→ còn **một** lần `require` (được opcache cache luôn).

**Bước 1 — sinh manifest lúc build.** `@wordpress/scripts` 28.6 (bản đang dùng) chưa có lệnh
`build-blocks-manifest`; hoặc nâng wp-scripts, hoặc thêm một script nhỏ:

```js
// scripts/build-blocks-manifest.mjs — chạy sau `wp-scripts build`
import { readdirSync, readFileSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';

const dir = 'build/blocks';
const out = {};

for ( const slug of readdirSync( dir ) ) {
    try {
        out[ slug ] = JSON.parse( readFileSync( join( dir, slug, 'block.json' ), 'utf8' ) );
    } catch {
        /* thư mục không phải block — bỏ qua */
    }
}

const php = `<?php\n// Sinh tự động bởi scripts/build-blocks-manifest.mjs — ĐỪNG sửa tay.\nreturn ${
    JSON.stringify( out, null, 1 )
        .replace( /^(\s*)"([^"]+)":/gm, '$1"$2" =>' )
        .replace( /[{]/g, 'array(' )
        .replace( /[}]/g, ')' )
        .replace( /\[/g, 'array(' )
        .replace( /\]/g, ')' )
};\n`;

writeFileSync( join( dir, 'blocks-manifest.php' ), php );
console.log( `✓ blocks-manifest.php: ${ Object.keys( out ).length } block` );
```

> Đoạn chuyển JSON → mảng PHP ở trên là bản tối giản cho dễ đọc; nếu nâng được `@wordpress/scripts`
> lên bản có sẵn `wp-scripts build-blocks-manifest` thì **dùng lệnh chính chủ**, đừng tự viết.

```jsonc
// package.json
"build": "wp-scripts build && node scripts/build-blocks-manifest.mjs",
```

**Bước 2 — đăng ký collection trước khi đăng ký block.** Plugin đang yêu cầu WP 6.4 nên phải có
nhánh dự phòng:

```php
// includes/class-block-manager.php — đầu register_blocks()
$manifest = FLEXA_BLOCK_DIR . 'build/blocks/blocks-manifest.php';
if ( function_exists( 'wp_register_block_metadata_collection' ) && file_exists( $manifest ) ) {
    // WP 6.7+: một lần require thay cho N lần đọc block.json.
    wp_register_block_metadata_collection( FLEXA_BLOCK_DIR . 'build/blocks', $manifest );
}
// … vòng lặp register_block_type( $path ) giữ nguyên, core tự ưu tiên collection.
```

Không phải sửa vòng lặp đăng ký: `register_block_type_from_metadata()` tự kiểm
`WP_Block_Metadata_Registry::get_metadata()` trước khi rơi về đọc file.

**Cách kiểm chứng:**

```bash
npm run build
ls -la build/blocks/blocks-manifest.php     # phải tồn tại
```

Rồi so thời gian `init` trước/sau (Query Monitor hoặc `microtime` quanh `register_blocks()`).
Kỳ vọng: phần đọc metadata tụt từ ~18 ms (76 block) về gần 0.

**Cảnh báo:** manifest là **file build**. Nếu quên chạy lại sau khi sửa `block.json`, block sẽ đăng ký
bằng metadata cũ — kiểu bug rất khó đoán. Ràng nó vào `npm run build` như trên, đừng để thành bước
thủ công.

### 5.7. Thứ tự triển khai đề xuất

1. **5.4 trước tiên** — dựng ngân sách với trần đúng bằng con số hiện tại (4 MB → tạm để 8.5 MB).
   Có thước đo trước khi sửa thì mới biết mỗi bước lời bao nhiêu.
2. **5.6** — chỉ thêm một file build + 4 dòng PHP, nhưng là thứ **duy nhất** trong danh sách này
   giúp được **front-end**. Làm sớm.
3. **5.3** — nhẹ, rủi ro thấp, cắt ngay 378 KB.
4. **5.2** — độc lập hoàn toàn với phần JS, test PHP có sẵn làm lưới an toàn.
5. **5.1** — nặng nhất, làm cuối, siết trần ở 5.4 xuống ngay sau khi xong.

---

## 6. Nguyên tắc cho block mới (áp dụng ngay từ bây giờ)

1. **Không thêm thư viện bên thứ ba vào bundle editor.** Cần thì `import()` động ở `view.ts`.
2. **Không copy helper giữa các block** — mỗi bản sao là thêm KB × số block. Đây chính là lý do
   `npm run lint:reuse` tồn tại; xem `docs/huong-dan-nhan-ban-block.md` §4.
3. **`example` trong `block.json` phải nhẹ.** Preview trong inserter render thật; dùng
   `ExamplePreviewSkeleton` thay vì dựng nội dung mẫu phức tạp.
4. **Generator chỉ sinh CSS khi user thực sự chọn giá trị** (guide §6.1). Ngoài chuyện kế thừa theme,
   nó giữ cho CSS inline mỗi trang ngắn.
5. **Block phụ thuộc plugin ngoài phải khai `is_woo`-style gating**, để site không cài plugin đó
   không phải trả giá gì.

---

## 7. Còn một ẩn số chưa đo

**CSS inline lưu trong post meta của từng trang.** Đây là thứ duy nhất trong toàn bộ chuỗi front-end
tăng theo *độ phức tạp trang*, và nó nằm ngay trong `<head>` của khách. Chưa đo được vì site
`template-demo` đang tắt lúc viết tài liệu này.

Cách đo khi site chạy (cổng MySQL của template-demo là 10059):

```sql
SELECT post_id, LENGTH(meta_value)/1024 AS kb
FROM wp_postmeta
WHERE meta_key = '_flexa_block_css'
ORDER BY kb DESC
LIMIT 10;
```

Ngưỡng đáng lo: một trang vượt ~50 KB CSS inline. Nếu chạm ngưỡng, hướng xử lý là ghi CSS ra file
tĩnh trong `uploads/` và enqueue thay vì inline — nhưng **đừng làm trước khi có số đo**, vì inline
đang tiết kiệm được một HTTP request và luôn khớp phiên bản nội dung.

Lưu ý: con số này **không** tăng theo số block plugin có, mà theo số block người dùng đặt lên trang.

## 8. Tóm tắt một dòng

**Asset front-end không nặng theo số block, nhưng PHP thì có: đăng ký block tốn ~18 ms/request ở 76
block và tăng tuyến tính (sửa bằng block metadata collection, mục 5.6); còn lại là tài sản editor
8 MB JS + 1 MB CSS mà phần lớn là lặp (sửa bằng `splitChunks`, mục 5.1) và 63 file generator PHP nạp
thừa mỗi request (sửa bằng autoloader, mục 5.2) — làm đủ bốn việc thì 200 block là hoàn toàn khả thi.**
