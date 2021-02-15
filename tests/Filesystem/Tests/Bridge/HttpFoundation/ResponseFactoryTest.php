<?php

namespace Zenstruck\Filesystem\Tests\Bridge\HttpFoundation;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Filesystem\Bridge\HttpFoundation\ResponseFactory;
use Zenstruck\Filesystem\Bridge\HttpFoundation\StreamedFileResponse;
use Zenstruck\Filesystem\FilesystemFactory;
use Zenstruck\Filesystem\Node\File;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ResponseFactoryTest extends TestCase
{
    /**
     * @test
     * @dataProvider fileProvider
     */
    public function can_create(File $file, string $expectedResponseClass): void
    {
        \ob_start();
        $response = ResponseFactory::create($file)->prepare(Request::create(''))->send();
        $output = \ob_get_clean();

        $this->assertInstanceOf($expectedResponseClass, $response);
        $this->assertTrue($response->headers->has('last-modified'));
        $this->assertSame($file->lastModified()->format('Y-m-d O'), (new \DateTime($response->headers->get('last-modified')))->format('Y-m-d O'));
        $this->assertTrue($response->headers->has('content-type'));
        $this->assertStringContainsString($file->mimeType(), $response->headers->get('content-type'));
        $this->assertFalse($response->headers->has('content-disposition'));
        $this->assertSame($file->contents(), $output);
    }

    /**
     * @test
     * @dataProvider fileProvider
     */
    public function can_create_as_inline(File $file, string $expectedResponseClass): void
    {
        \ob_start();
        $response = ResponseFactory::inline($file)->prepare(Request::create(''))->send();
        $output = \ob_get_clean();

        $this->assertInstanceOf($expectedResponseClass, $response);
        $this->assertTrue($response->headers->has('last-modified'));
        $this->assertSame($file->lastModified()->format('Y-m-d O'), (new \DateTime($response->headers->get('last-modified')))->format('Y-m-d O'));
        $this->assertTrue($response->headers->has('content-type'));
        $this->assertStringContainsString($file->mimeType(), $response->headers->get('content-type'));
        $this->assertTrue($response->headers->has('content-disposition'));
        $this->assertSame("inline; filename={$file->filename()}", $response->headers->get('content-disposition'));
        $this->assertSame($file->contents(), $output);
    }

    /**
     * @test
     * @dataProvider fileProvider
     */
    public function can_create_as_attachment(File $file, string $expectedResponseClass): void
    {
        \ob_start();
        $response = ResponseFactory::attachment($file)->prepare(Request::create(''))->send();
        $output = \ob_get_clean();

        $this->assertInstanceOf($expectedResponseClass, $response);
        $this->assertTrue($response->headers->has('last-modified'));
        $this->assertSame($file->lastModified()->format('Y-m-d O'), (new \DateTime($response->headers->get('last-modified')))->format('Y-m-d O'));
        $this->assertTrue($response->headers->has('content-type'));
        $this->assertStringContainsString($file->mimeType(), $response->headers->get('content-type'));
        $this->assertTrue($response->headers->has('content-disposition'));
        $this->assertSame("attachment; filename={$file->filename()}", $response->headers->get('content-disposition'));
        $this->assertSame($file->contents(), $output);
    }

    public static function fileProvider(): iterable
    {
        yield [(new FilesystemFactory())->create(__DIR__.'/../../Fixture/directory')->file('nested/file2.txt'), BinaryFileResponse::class];

        $filesystem = (new FilesystemFactory())->create('in-memory:');
        $filesystem->write('some/file.txt', 'content');

        yield [$filesystem->file('some/file.txt'), StreamedFileResponse::class];

        $filesystem = (new FilesystemFactory())->create('temp-file(in-memory:)');
        $filesystem->write('some/file.txt', 'content');

        yield [$filesystem->file('some/file.txt'), BinaryFileResponse::class];
    }
}
