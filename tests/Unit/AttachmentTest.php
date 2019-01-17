<?php

declare(strict_types=1);

namespace Orchid\Tests\Unit;

use Illuminate\Http\UploadedFile;
use Orchid\Attachment\File;
use Orchid\Attachment\Models\Attachment;
use Orchid\Platform\Models\User;
use Orchid\Tests\TestUnitCase;

/**
 * Class AttachmentTest.
 */
class AttachmentTest extends TestUnitCase
{
    /**
     * @var string
     */
    public $disk;

    /**
     * @test
     */
    public function testAttachmentFile()
    {
        $file       = UploadedFile::fake()->create('document.xml', 200);
        $attachment = new File($file, $this->disk);
        $upload     = $attachment->load();

        $this->assertEquals([
            'size' => $file->getSize(),
            'name' => $file->name,
        ], [
            'size' => $upload->size,
            'name' => $upload->original_name,
        ]);

        $this->assertContains($upload->name . '.xml', $upload->url());
    }

    /**
     * @test
     */
    public function testAttachmentImage()
    {
        $file = UploadedFile::fake()->image('avatar.jpg', 1920, 1080)->size(100);

        $attachment = new File($file, $this->disk);
        $upload     = $attachment->load();

        $this->assertNotNull($upload->url());
    }

    /**
     * @test
     */
    public function testAttachmentUser()
    {
        $user = factory(User::class)->create();

        $this->actingAs($user);

        $file       = UploadedFile::fake()->create('user.jpg', 1920, 1080);
        $attachment = new File($file, $this->disk);
        $upload     = $attachment->load();

        $this->assertEquals($upload->user()->first()->email, $user->email);
    }

    /**
     * @test
     */
    public function testAttachmentUrlLink()
    {
        $file       = UploadedFile::fake()->create('example.jpg', 1920, 1080);
        $attachment = new File($file, $this->disk);
        $upload     = $attachment->load();

        $this->assertNotNull($upload->getUrlAttribute());
        $this->assertNotNull($upload->url());
    }

    /**
     * @test
     */
    public function testAttachmentUrlLinkNotFound()
    {
        $upload = new Attachment();

        $this->assertNull($upload->url());
        $this->assertEquals($upload->url('default'), 'default');
    }

    /**
     * @test
     */
    public function testAttachmentMimeType()
    {
        $file       = UploadedFile::fake()->create('user.jpg', 1920, 1080);
        $attachment = new File($file, $this->disk);
        $upload     = $attachment->load();

        $this->assertEquals($upload->getMimeType(), 'image/jpeg');
    }

    /**
     * @test
     */
    public function testAttachmentDelete()
    {
        $file       = UploadedFile::fake()->create('delete.jpg');
        $attachment = new File($file, $this->disk);
        $upload     = $attachment->load();

        $delete = $upload->delete();

        $this->assertTrue($delete);
    }

    /**
     * @test
     */
    public function testDuplicateAttachmentUpload()
    {
        $file  = UploadedFile::fake()->create('duplicate.jpg');
        $clone = clone $file;

        $upload = (new File($file, $this->disk))->load();
        $clone  = (new File($clone, $this->disk))->load();


        $this->assertEquals($upload->url(), $clone->url());
        $this->assertNotEquals($upload->id, $clone->id);

        $upload->delete();
        $this->assertNotNull($clone->url());
    }

    /**
     * @test
     */
    public function testUnknownMimeTypeAttachmentUpload()
    {
        $file   = UploadedFile::fake()->create('duplicate.gyhkjfewfowejg');
        $upload = (new File($file, $this->disk))->load();

        $this->assertEquals($upload->getMimeType(), 'unknown');
    }

    /**
     * @test
     */
    public function testUnknownExtensionAttachmentUpload()
    {
        $file   = UploadedFile::fake()->create('unknown-file');
        $upload = (new File($file, $this->disk))->load();

        $this->assertEquals($upload->extension, 'bin');
    }

    protected function setUp()
    {
        parent::setUp();
        $this->disk = 'public';
    }
}
