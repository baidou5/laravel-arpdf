![ArPDF Logo](https://raw.githubusercontent.com/baidou5/laravel-arpdf/main/arpdf.jpg)

# Laravel ArPDF

A Laravel package for generating **PDF files with full Arabic and English support**, including **UTF-8**, **RTL**, and **custom font integration**, using the powerful **mPDF** engine — all **without relying on external services**.

---

## 🚀 Features

- ✔️ Fully supports **Arabic**, **RTL**, **UTF-8**, and mixed languages  
- ✔️ Clean & simple **Laravel-style API**  
- ✔️ Includes **Facade** + **Auto-Discovery**  
- ✔️ Works with **Laravel 8, 9, 10, 11, 12**  
- ✔️ Supports **custom Arabic fonts** (Cairo, Amiri, etc.)  
- ✔️ High-quality rendering powered by **mPDF**  
- ✔️ Save, download, or stream PDFs from your controller  

---

## 📦 Installation

Install the package via Composer:

```bash
composer require baidouabdellah/laravel-arpdf
```

### ✔ Laravel 8+  
No configuration is required — Laravel automatically discovers the package.

### ✔ For Laravel < 8 (Manual Registration)

Add the service provider to `config/app.php`:

```php
'providers' => [
    Baidouabdellah\LaravelArpdf\ArPDFServiceProvider::class,
],
```

### (Optional) Publish Configuration

```bash
php artisan vendor:publish --provider="Baidouabdellah\LaravelArpdf\ArPDFServiceProvider"
```

This allows customizing fonts, default direction (RTL/LTR), and mPDF settings.

---

## 🧪 Usage Example

### Controller Demo

```php
use Baidouabdellah\LaravelArpdf\Facades\ArPDF;


public function testPdf()
{
    $html = '<h1 style="text-align:right">مرحبا بالعالم</h1>
             <p>هذا مثال PDF باستخدام Laravel ArPDF.</p>';

    return ArPDF::direction('rtl')
        ->loadHTML($html)
        ->download('example.pdf'); //stream or download
}
```

---

## 📄 Blade View Example

Create a view such as:

`resources/views/pdf/invoice.blade.php`

```html
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: 'cairo';
            direction: rtl;
            text-align: right;
        }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p>مرحبا بك في نظام الفواتير.</p>
</body>
</html>
```

Render and export:

```php
$html = view('pdf.invoice', [
    'title' => 'فاتورة رقم 123'
])->render();

return ArPDF::loadHTML($html)->download('invoice-123.pdf');
```

---

## 🔤 Custom Arabic Fonts

mPDF supports custom fonts such as **Cairo**, **Amiri**, **Scheherazade**, etc.

To use your own fonts:

1. Place fonts inside a folder, e.g.:  
   `resources/fonts/`
2. Register them inside `ArPDF.php` (font bootstrap section)
3. Use them in CSS:

```css
body {
    font-family: 'cairo';
}
```

---

## 📞 Support

If you encounter any issue, feel free to open a ticket here:  
👉 https://github.com/baidou5/laravel-arpdf/issues

---

### 👤 Author

**Abdellah Baidou**  
📱 Phone: **+212 661-176711**  
📧 Email: **baidou.abd@gmail.com**

---

## 📄 License

This package is licensed under the **MIT License**.  
See the [LICENSE](LICENSE) file for more information.
