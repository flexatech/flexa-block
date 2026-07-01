# Flexa Block

Bộ block Gutenberg responsive, hỗ trợ **dark mode** và **sinh CSS lúc lưu bài** (save-time CSS). Hiện ship block **Container** làm **block chuẩn** (khuôn mẫu kiến trúc) cho các block phát triển sau này.

- **Version:** 1.0.0
- **Yêu cầu:** WordPress 6.4+, PHP 7.4+
- **License:** GPLv3

---

## Mục lục
1. [Cách chạy plugin](#1-cách-chạy-plugin)
2. [Cách build](#2-cách-build)
3. [Cách test](#3-cách-test)
4. [Cấu trúc thư mục](#4-cấu-trúc-thư-mục)
5. [Cơ chế hoạt động (tóm tắt)](#5-cơ-chế-hoạt-động-tóm-tắt)

---

## 1. Cách chạy plugin

Plugin chạy như mọi plugin WordPress. Có 2 trường hợp:

### A. Chỉ dùng (không sửa code)
Thư mục `build/` đã có sẵn asset đã build, nên chỉ cần:
1. Đặt thư mục `flexa-block/` vào `wp-content/plugins/`.
2. Vào **WordPress Admin → Plugins → Flexa Block → Activate**.
3. Mở trình soạn thảo (post/page hoặc Site Editor), thêm block **Container** (nhóm Flexa).

### B. Vừa dùng vừa phát triển
Cần cài dependency và build lại asset trước khi kích hoạt — xem [phần 2](#2-cách-build).

> **Lưu ý môi trường:** dự án này đang chạy trên **Local by Flywheel**. Local đóng gói sẵn PHP/MySQL nhưng **không expose `php`/`composer` ra PATH hệ thống**. Vì vậy lệnh `npm` chạy bình thường, còn lệnh `composer`/`phpunit` cần cấu hình thêm — xem [phần 3](#3-cách-test).

---

## 2. Cách build

Yêu cầu: **Node.js** (khuyến nghị ≥ 18) và **npm**.

```bash
# Từ thư mục plugin: wp-content/plugins/flexa-block

npm install          # cài dependency (1 lần đầu)

npm run build        # build production → xuất vào build/
npm run start        # build dev + watch (tự build lại khi sửa src/)
```

Các lệnh khác:

```bash
npm run format       # tự format code theo chuẩn WordPress
npm run lint:js      # kiểm tra ESLint
npm run lint:css     # kiểm tra Stylelint
npm run typecheck    # kiểm type TypeScript (tsc --noEmit, không xuất file)
npm run check        # typecheck + test JS
```

> **Source viết bằng TypeScript** (`.tsx`/`.ts`). Babel (qua `@wordpress/scripts`) strip type lúc build nên `build/` vẫn ra `.js` như cũ; việc kiểm type là bước riêng `npm run typecheck`. Type cho attributes ở [`src/types.ts`](src/types.ts); WP packages khai báo loose ở [`src/global.d.ts`](src/global.d.ts).

Asset sau khi build nằm ở `build/blocks/container/` và được PHP nạp ở runtime (chỉ trên trang thực sự dùng block).

---

## 3. Cách test

Plugin có **2 lớp test**:

| Lớp | Kiểm tra gì | Công cụ | Chạy được ngay? |
|---|---|---|---|
| **JS** | Util editor: cascade responsive, format giá trị (`effective`, `withUnit`, `parseUnit`, `spacingShorthand`) | Jest (qua `@wordpress/scripts`) | ✅ Có (đã có Node) |
| **PHP** | Lõi sinh CSS: `CSS_Builder`, `CSS_Helpers`, `Container_CSS` (selector, responsive, dark mode) | PHPUnit | ⚙️ Cần PHP + Composer |

### 3.1. Test JS — chạy ngay

```bash
npm install            # nếu chưa cài
npm test               # chạy toàn bộ test JS
npm run test:watch     # chế độ watch
```

File test đặt cạnh source: `src/**/*.test.ts` (ví dụ `src/utils/index.test.ts`). File test được loại khỏi `tsc` (chạy bằng Jest), nên không cần cài `@types/jest`.

### 3.2. Test PHP

Test PHP **không cần** cài WordPress hay MySQL. File `tests/test-init.php` đã stub sẵn vài hàm WordPress để test chạy độc lập, rất nhanh.

Máy này đã cài **PHP 8.2 + Composer toàn cục** (ở `%USERPROFILE%\php`, đã thêm vào PATH). Chạy thẳng:

```bash
composer install     # cài PHPUnit + PHPStan vào vendor/ (1 lần)
composer test        # chạy toàn bộ test PHP (= phpunit)

vendor/bin/phpunit                                   # hoặc gọi phpunit trực tiếp
vendor/bin/phpunit --filter test_box_shadow_rendered # lọc 1 test
```

### 3.3. Phân tích tĩnh PHP (PHPStan)

Ngoài test, plugin còn bật **kiểu chặt** (`declare(strict_types=1)` ở mọi file PHP) và **phân tích tĩnh** bằng PHPStan (level 5, kèm WordPress stubs). Công cụ này đọc code mà không cần chạy, bắt sớm lỗi sai kiểu / sai tham số / truy cập key mảng không tồn tại.

```bash
composer analyse     # chạy PHPStan (cấu hình ở phpstan.neon.dist)
composer check       # chạy cả PHPStan + PHPUnit
```

> **Lưu ý:** PHP đứng riêng ở `%USERPROFILE%\php` (độc lập với PHP của Local). Cấu hình extension nằm ở `%USERPROFILE%\php\php.ini`. Nếu mở terminal đang chạy từ trước khi cài, hãy **mở terminal mới** để nhận PATH.

### 3.4. Thêm test cho một block mới

Test được thiết kế để **tái sử dụng**:
- `CssBuilderTest` và `CssHelpersTest` kiểm lõi mà **mọi block dùng chung** → không cần viết lại.
- Để test block mới: copy `tests/php/ContainerCssTest.php`, kế thừa `CssTestCase`, dùng sẵn `genCss()` + `assertCssHas()`, rồi đổi generator + attribute. Nhớ thêm 1 dòng `require_once` generator mới vào `tests/test-init.php`.

---

## 4. Cấu trúc thư mục

```
flexa-block/
├── flexa-block.php              # Entry point của plugin
├── includes/                    # Backend PHP (namespace Flexa\Block)
│   ├── class-css-builder.php        # Fluent CSS builder + media query + minify
│   ├── class-css-helpers.php        # Attribute → CSS (spacing, border, background, shadow, dark)
│   ├── class-css-generator-service.php
│   ├── class-dark-mode-settings.php
│   ├── class-global-styles.php      # Design tokens (:root --flexa-* + dark overrides)
│   ├── admin/
│   │   └── class-admin.php           # Trang settings + REST API + menu
│   └── css-generators/
│       └── class-container-css.php  # Generator riêng của block Container
├── src/                         # Source frontend/editor (React + TypeScript)
│   ├── types.ts                     # Type cho attributes (dùng chung mọi block)
│   ├── global.d.ts                  # Ambient: @wordpress/* (loose), *.scss, window.flexaBlock*
│   ├── admin/                        # App React của trang settings (index.tsx)
│   ├── blocks/container/             # Block Container (block.json, edit.tsx, render.php, view.ts, ...)
│   ├── components/                   # Panel & control dùng chung (.tsx)
│   ├── hooks/  ·  utils/             # Hook + util (có util/index.test.ts)
├── build/                       # Asset đã build (do npm tạo)
├── tsconfig.json                # Cấu hình TypeScript (strict, tsc --noEmit)
├── tests/                       # Test PHP
│   ├── test-init.php                 # "Bootstrap" PHPUnit (define ABSPATH + stub WP)
│   ├── phpstan-bootstrap.php         # Khai báo hằng plugin cho PHPStan
│   ├── CssTestCase.php               # Base class dùng chung cho test block
│   └── php/                          # CssBuilderTest, CssHelpersTest, ContainerCssTest
├── composer.json                # Dev dep: phpunit, phpstan
├── phpunit.xml.dist             # Cấu hình PHPUnit
├── phpstan.neon.dist            # Cấu hình PHPStan (level 5 + WP stubs)
├── package.json                 # Script build/lint/test JS
├── README.md                    # File này
└── readme.txt                   # Readme chuẩn WordPress.org
```

---

## 5. Cơ chế hoạt động (tóm tắt)

- **Save-time CSS:** khi lưu post/template, plugin phân tích nội dung, sinh CSS một lần và lưu vào post meta `_flexa_block_css`. Frontend chỉ in inline CSS này trên trang thực sự dùng block → không sinh CSS lại mỗi lần tải.
- **Responsive cascade:** thuộc tính có cấu trúc `desktop / tablet / mobile`; tablet kế thừa desktop, mobile kế thừa tablet (đổ xuống). Breakpoint: tablet `max-width: 1024px`, mobile `max-width: 767px`.
- **Dark mode:** mỗi màu là cặp `{ light, dark }`. CSS dark được phát qua `@media (prefers-color-scheme: dark)` và/hoặc `[data-theme="dark"]`, tùy cấu hình trong `Dark_Mode_Settings`.
- **Design tokens (global styles):** `Global_Styles` in một bộ biến CSS dùng chung lên `:root` (`--flexa-color-*`, `--flexa-space-*`, `--flexa-font-size-*`, `--flexa-radius-*`) + bản dark override. Block tham chiếu `var(--flexa-...)` để nhất quán. Tùy biến qua filter `flexa_block_design_tokens` (light) và `flexa_block_design_tokens_dark` (dark).
- **Lazy-load ảnh nền:** bật toggle "Lazy load image" trong panel Background; `view.js` dùng `IntersectionObserver` để chỉ tải ảnh khi gần khung nhìn (url được gate sau class `.flexa-bg-loaded`).
- **Mở rộng (thêm block):** mỗi block khai báo `name` + `generator` trong `Block_Manager::BASE_BLOCKS`; `CSS_Generator_Service` build map sinh-CSS **từ catalog đó** (không hardcode). Generator trong `includes/css-generators/` được **auto-load bằng glob**. Add-on bên ngoài đăng ký block qua filter `flexa_block_blocks` và nối generator qua filter `flexa_block_css_generators`. → Thêm 1 block = thêm 1 entry catalog + thả 1 file generator vào thư mục. **Checklist chi tiết:** [docs/huong-dan-nhan-ban-block.md](docs/huong-dan-nhan-ban-block.md).

### Trang Settings (admin)

Vào **WordPress Admin → menu "Flexa Block"** để:
- Bật/tắt **dark mode** + chọn phương thức (`prefers-color-scheme` / `[data-theme="dark"]`).
- Bật **CSS specificity boost** (thêm `body ` trước selector để chắc chắn đè theme).
- **Bật/tắt từng block** (block lõi luôn bật).

Cài đặt lưu trong option `flexa_block_settings` qua REST (`/wp-json/flexa-block/v1/settings`, yêu cầu quyền `manage_options`).

---

## 6. Xử lý sự cố (troubleshooting)

### 6.1. CSS không xuất hiện / cũ ở frontend

CSS được sinh **lúc lưu bài** và cache vào post meta `_flexa_block_css` (kèm `_flexa_block_css_version`). Nếu frontend không thấy CSS hoặc thấy CSS cũ:

1. **Mở lại bài và bấm Update** — đây là cách tái sinh CSS chắc chắn nhất.
2. CSS chỉ in trên trang **thực sự chứa block** — kiểm tra block còn trong nội dung không.
3. Sau khi **cập nhật phiên bản plugin**, CSS cũ tự được coi là hết hạn (`needs_regeneration()` so sánh version) và sinh lại ở lần lưu kế tiếp.
4. Muốn xoá cache thủ công: xoá 2 post meta `_flexa_block_css` và `_flexa_block_css_version` của bài đó (hoặc lưu lại bài).

### 6.2. Một block lỗi nhưng các block khác vẫn ổn

Mỗi block được **cách ly khi sinh CSS**: nếu một generator ném lỗi, plugin bỏ qua đúng block đó và vẫn sinh CSS cho phần còn lại — **không làm hỏng cả trang hay chặn việc lưu bài**.

Để xem block nào lỗi, bật `WP_DEBUG` trong `wp-config.php`:

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
```

Lỗi sẽ được ghi vào `wp-content/debug.log` theo dạng:

```
[Flexa Block] CSS generation failed for block "flexa/...": <thông điệp lỗi> in <file>:<dòng>
```

### 6.3. Dark mode không hiển thị

Xem điều kiện ở mục [Cơ chế hoạt động](#5-cơ-chế-hoạt-động-tóm-tắt): block phải có **giá trị màu Dark**, đã **lưu bài**, và trạng thái tối phải được kích hoạt (`@media (prefers-color-scheme: dark)` khi OS ở chế độ tối, hoặc có `[data-theme="dark"]` trên trang).
