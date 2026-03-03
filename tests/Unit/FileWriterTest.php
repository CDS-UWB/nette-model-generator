<?php

namespace Tests\Unit;

use Cds\NetteModelGenerator\FileWriter;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\Printer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(FileWriter::class)]
class FileWriterTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/nette-model-generator-test-' . uniqid();
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    #[Test]
    public function writeStringContentCreatesNewFile(): void
    {
        $writer = new FileWriter();
        $filePath = $this->tempDir . '/test.php';
        $content = '<?php echo "test";';
        $printer = new Printer();

        $result = $writer->writeFile($filePath, $content, $printer);

        $this->assertTrue($result);
        $this->assertFileExists($filePath);
        $this->assertEquals($content, file_get_contents($filePath));
    }

    #[Test]
    public function writePhpFileContentConvertsToString(): void
    {
        $writer = new FileWriter();
        $filePath = $this->tempDir . '/test.php';
        $printer = new Printer();

        $phpFile = new PhpFile();
        $phpFile->addComment('Test file');

        $result = $writer->writeFile($filePath, $phpFile, $printer);

        $this->assertTrue($result);
        $this->assertFileExists($filePath);

        $content = file_get_contents($filePath);
        $this->assertIsString($content);
        $this->assertStringContainsString('Test file', $content);
    }

    #[Test]
    public function writeFileCreatesDirectoriesIfNotExist(): void
    {
        $writer = new FileWriter();
        $filePath = $this->tempDir . '/subdir/deep/nested/test.php';
        $content = '<?php test;';
        $printer = new Printer();

        $result = $writer->writeFile($filePath, $content, $printer);

        $this->assertTrue($result);
        $this->assertFileExists($filePath);
        $this->assertDirectoryExists(dirname($filePath));
    }

    #[Test]
    public function writeFileReturnsFalseWhenContentHasNotChanged(): void
    {
        $writer = new FileWriter();
        $filePath = $this->tempDir . '/test.php';
        $content = '<?php echo "unchanged";';
        $printer = new Printer();

        // Write file first time
        $result1 = $writer->writeFile($filePath, $content, $printer);
        $this->assertTrue($result1);

        // Write same content second time
        $result2 = $writer->writeFile($filePath, $content, $printer);
        $this->assertFalse($result2);
    }

    #[Test]
    public function writeFileReturnsTrueWhenContentHasChanged(): void
    {
        $writer = new FileWriter();
        $filePath = $this->tempDir . '/test.php';
        $printer = new Printer();

        // Write file first time
        $result1 = $writer->writeFile($filePath, '<?php echo "first";', $printer);
        $this->assertTrue($result1);

        // Write different content
        $result2 = $writer->writeFile($filePath, '<?php echo "second";', $printer);
        $this->assertTrue($result2);
        $this->assertEquals('<?php echo "second";', file_get_contents($filePath));
    }

    #[Test]
    public function writeFileWithEmptyContent(): void
    {
        $writer = new FileWriter();
        $filePath = $this->tempDir . '/empty.php';
        $content = '';
        $printer = new Printer();

        $result = $writer->writeFile($filePath, $content, $printer);

        $this->assertTrue($result);
        $this->assertFileExists($filePath);
        $this->assertEquals('', file_get_contents($filePath));
    }

    private function removeDirectory(string $dir): void
    {
        $files = scandir($dir);
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}
