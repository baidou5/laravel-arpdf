![ArPDF Logo](https://raw.githubusercontent.com/baidou5/laravel-arpdf/main/arpdf.jpg)
# Laravel ArPDF

A Laravel package for generating PDF files with support for both English and Arabic languages without relying on external libraries.

## Installation

To install the `laravel-arpdf` package, follow these steps:

1. **Add the package to your Laravel project using Composer**:

   ```bash
   composer require baidouabdellah/laravel-arpdf:dev-main
   ```

2. **Register the Service Provider (if using Laravel < 5.5)**:

   In your `config/app.php` file, add the following line to the `providers` array:

   ```php
   Baidouabdellah\LaravelArpdf\ArPDFServiceProvider::class,
   ```

3. **Publish the configuration file (optional)**:

   You can publish the configuration file to customize the package settings:

   ```bash
   php artisan vendor:publish --provider="Baidouabdellah\LaravelArpdf\ArPDFServiceProvider"
   ```

## Usage

To use the package, you can access the PDF generation functionality in your controller:

Demo Controller

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Baidouabdellah\LaravelArpdf\ArPDF;

class PdfController extends Controller
{
    public function generatePdf()
    {
        $pdf = app(ArPDF::class);

        // Example of adding text
        $pdf->addText(50, 800, 12, 'Hello World');
        $pdf->addArabicText(50, 780, 12, 'مرحبا بالعالم');

        // Save or stream the PDF
        $pdf->save('output/sample.pdf');
        // Or stream directly to the browser
        // $pdf->stream('document.pdf');
    }
}

```

### Generating a PDF

Here’s an example of how to generate a simple PDF:

```php
$pdf = app('ArPDF');
$pdf->setTitle('Sample PDF');
$pdf->addPage();
$pdf->writeHTML('<h1>Hello World</h1>');
$pdf->output('sample.pdf');
```
### Customizing Arabic Font
If you need to customize the Arabic font used in the PDFs, follow these steps:

1. **Add the Arabic font files**:
   Place your Arabic font files (e.g., TTF or OTF) in the `resources/fonts` directory of your Laravel project.

2. **Configure the font in your code**:
   In your PDF generation code, you can specify the font like this:

   ```php
   $pdf = app('ArPDF');
   $pdf->setFont('path/to/your/font.ttf'); // Specify the path to your Arabic font
    ```
3. **Ensure the font supports Arabic characters**:
     Make sure the font you are using supports Arabic characters to display them correctly in the PDF.

4. **Example of setting the Arabic font:**
  Here’s an example of how to set an Arabic font in your PDF:
```php
$pdf = app('ArPDF');
$pdf->setTitle('Sample PDF');
$pdf->addPage();
$pdf->setFont('resources/fonts/YourArabicFont.ttf');
$pdf->writeHTML('<h1>مرحبا بالعالم</h1>'); // Example of Arabic text
$pdf->output('sample.pdf');
 ```

## Support

If you encounter any issues, please open an issue on the [GitHub repository](https://github.com/baidou5/laravel-arpdf/issues).

---
- **Abdellah Baidou**
- Phone: +212 661-176711
- Email: baidou.abd@gmail.com

----

## License


This package is licensed under the MIT License. See the [LICENSE](LICENSE) file for more information.
