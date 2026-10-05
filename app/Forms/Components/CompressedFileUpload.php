<?php

namespace App\Forms\Components;

use Closure;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Color;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Format;
use Intervention\Image\Geometry\Factories\RectangleFactory;
use Intervention\Image\ImageManager;
use Intervention\Image\Typography\FontFactory;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class CompressedFileUpload extends FileUpload
{
    // Logika penamaan file (string atau closure)
    protected Closure | string | null $fileNameGenerator = null;

    // Teks watermark (string atau closure), null = tanpa watermark
    protected Closure | string | null $watermarkText = null;

    // Batas ukuran & kualitas hasil kompres di server
    protected int $compressWidth = 1280;
    protected int $compressHeight = 1280;
    protected int $compressQuality = 75;

    // Atur nama file dari Form
    public function fileName(Closure | string $name): static
    {
        $this->fileNameGenerator = $name;

        return $this;
    }

    // Atur teks watermark dari Form
    public function watermark(Closure | string | null $text): static
    {
        $this->watermarkText = $text;

        return $this;
    }

    // Atur ukuran maksimum & kualitas WebP dari Form
    public function compressTo(int $width, int $height, int $quality = 75): static
    {
        $this->compressWidth = $width;
        $this->compressHeight = $height;
        $this->compressQuality = $quality;

        return $this;
    }

    // Tempel strip hitam transparan + teks putih di bagian bawah gambar (API Intervention v4)
    protected function applyTextWatermark($image): void
    {
        if (blank($this->watermarkText)) {
            return;
        }

        $text = (string) $this->evaluate($this->watermarkText);
        $fontPath = resource_path('fonts/Roboto-SemiBold.ttf');

        // Kalau teks kosong atau font belum ada, lewati saja (jangan bikin upload gagal)
        if (blank($text) || ! file_exists($fontPath)) {
            return;
        }

        $width = $image->width();
        $height = $image->height();

        $fontSize = max(14, (int) round($width / 40));
        $barHeight = (int) round($fontSize * 2.2);
        $padding = (int) round($fontSize * 0.8);

        // 1. Strip hitam semi-transparan di bawah
        //    v4: drawRectangle hanya menerima callback, posisi diatur lewat at()
        $image->drawRectangle(function (RectangleFactory $rectangle) use ($width, $height, $barHeight): void {
            $rectangle->at(0, $height - $barHeight);
            $rectangle->size($width, $barHeight);
            $rectangle->background(Color::rgb(0, 0, 0, .55));
        });

        // 2. Teks putih di atas strip
        //    Titik y = garis dasar teks, dihitung agar teks berada di tengah strip
        $baseline = $height - (int) round(($barHeight - $fontSize) / 2);

        $image->text($text, $padding, $baseline, function (FontFactory $font) use ($fontPath, $fontSize): void {
            $font->filepath($fontPath);
            $font->size($fontSize);
            $font->color('ffffff');
        });
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->image();
        $this->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp']);

        // Batas default 5 MB agar server tidak terbebani (bisa ditimpa dari Form)
        $this->maxSize(5120);

        $this->saveUploadedFileUsing(function (TemporaryUploadedFile $file) {

            // 1. Baca gambar
            $manager = ImageManager::usingDriver(Driver::class);
            $image = $manager->decodePath($file->getRealPath());

            // 2. Hanya mengecilkan, tidak pernah memperbesar
            $image->scaleDown(
                width: $this->compressWidth,
                height: $this->compressHeight
            );

            // 3. Tempel watermark (setelah resize, sebelum encode)
            $this->applyTextWatermark($image);

            // 4. Encode ke WebP
            $encoded = $image->encodeUsingFormat(Format::WEBP, quality: $this->compressQuality);

            $folder = $this->getDirectory() ?? 'uploads';
            $disk = $this->getDiskName() ?? 'public';

            // 5. Tentukan nama dasar file
            $nameBase = null;

            if ($this->fileNameGenerator) {
                $customName = $this->evaluate($this->fileNameGenerator);
                $nameBase = Str::slug((string) $customName);
            }

            // Jika kosong, pakai UUID
            if (blank($nameBase)) {
                $nameBase = Str::uuid()->toString();
            }

            $filename = $nameBase . '.webp';
            $finalPath = $folder . '/' . $filename;

            // 6. Hindari nama bentrok
            if (Storage::disk($disk)->exists($finalPath)) {
                $filename = $nameBase . '-' . Str::random(4) . '.webp';
                $finalPath = $folder . '/' . $filename;
            }

            // 7. Simpan mengikuti visibility dari Filament
            Storage::disk($disk)->put($finalPath, (string) $encoded, [
                'visibility' => $this->getVisibility(),
            ]);

            // Bebaskan memori secepatnya
            unset($image, $encoded);

            return $finalPath;
        });
    }
}
