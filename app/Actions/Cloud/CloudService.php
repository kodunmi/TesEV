<?php

namespace App\Actions\Cloud;

use App\Enum\CloudTypeEnum;
use App\Jobs\Core\Cloud\LargeFileUpload;
use App\Repositories\Core\FileRepository;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\Storage;

class CloudService
{
    protected $s3;

    protected $cloudinary;

    protected $fileService;

    public function __construct()
    {
        $this->fileService = new FileRepository();
        $this->s3 = Storage::disk('s3');
        $this->cloudinary = Cloudinary::class;
    }

    public function upload($file, $provider, $folder, $owner_id, $name = null, $type = null, $extension = 'pdf')
    {
        try {
            if ($provider == CloudTypeEnum::S3) {

                if (!$extension) {
                    throw new \Exception('extension cannot be null if using s3');
                }

                $file_name = slug(now()) . '.' . $extension;

                $path = "{$folder}" . '/' . $file_name;

                $upload = $this->s3->put($path, $file);

                if (!$upload) {
                    return [
                        'status' => false,
                        'message' => 'File could not be uploaded',
                        'data' => null,
                        'code' => 400,
                    ];
                }

                if (!$this->s3->exists($path)) {
                    return [
                        'status' => false,
                        'message' => 'Path not found',
                        'data' => null,
                        'code' => 400,
                    ];
                }

                $url = $this->s3->url($path);

                $file = $this->fileService->create([
                    'name' => $name ? $name . '.' . $extension : $file_name,
                    'provider' => $provider,
                    'type' => $type,
                    'path' => $path,
                    'url' => $url,
                    'extension' => $extension,
                    'size' => $this->size($upload),
                    'folder' => $folder,
                    'owner_id' => $owner_id,
                ]);

                return [
                    'status' => true,
                    'message' => 'File uploaded',
                    'data' => $file,
                    'code' => 200,
                ];
            }

            $uploaded = $this->cloudinary::upload($file->getRealPath(), [
                'folder' => $folder,
            ]);

            $created_file = $this->fileService->create([
                'name' => $name ? $name : $file->hashName(),
                'provider' => $provider,
                'type' => $type,
                'path' => $folder,
                'url' => $uploaded->getSecurePath(),
                'extension' => $uploaded->getExtension(),
                'size' => $uploaded->getSize(),
                'file_id' => $uploaded->getPublicId(),
                'folder' => $folder,
                'owner_id' => $owner_id,
            ]);

            return [
                'status' => true,
                'message' => 'File uploaded',
                'data' => $created_file,
                'code' => 200,
            ];
        } catch (\Exception $e) {
            logError($e->getMessage(), [
                'location' => 'upload file'
            ]);

            return [
                'status' => false,
                'message' => $e->getMessage(),
                'data' => null,
                'code' => 400,
            ];
        }
    }

    public function url($path)
    {
        if (!$this->s3->exists($path)) {
            return false;
        }

        $url = $this->s3->url($path);

        return $url;
    }

    public function get($id)
    {
        try {
            $file = $this->fileService->findById($id);

            if (!$file) {
                return [
                    'status' => false,
                    'message' => 'File not found',
                    'data' => null,
                    'code' => 404,
                ];
            }

            if ($file->provider == CloudTypeEnum::CLOUDINARY->value) {
                if (!Storage::disk('cloudinary')->fileExists($file->file_id)) {
                    return [
                        'status' => false,
                        'message' => 'File not found on the cloud',
                        'data' => null,
                        'code' => 404,
                    ];
                }
                $get_file = $this->cloudinary::getFile($file->file_id);

                return [
                    'status' => true,
                    'message' => 'File found',
                    'data' => $get_file,
                    'code' => 200,
                ];
            }

            if (!$this->s3->exists($file->path)) {
                return [
                    'status' => false,
                    'message' => 'File not found on the cloud',
                    'data' => null,
                    'code' => 404,
                ];
            }

            $get_file = $this->s3->get($file->path);

            return [
                'status' => true,
                'message' => 'File found',
                'data' => $get_file,
                'code' => 200,
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage(),
                'data' => null,
                'code' => 400,
            ];
        }
    }

    public function size($path)
    {
        if (!$this->s3->exists($path)) {
            return false;
        }

        $file_size = $this->s3->size($path);

        return $file_size;
    }

    public function extension($path)
    {
        if (!$this->s3->exists($path)) {
            return false;
        }

        $fileInfo = pathinfo($path);

        $fileExtension = $fileInfo['extension'];

        return $fileExtension;
    }

    public function remove($id)
    {
        try {
            $file = $this->fileService->findById($id);

            if (!$file) {
                return [
                    'status' => false,
                    'message' => 'File not found',
                    'data' => null,
                    'code' => 404,
                ];
            }

            if ($file->provider == CloudTypeEnum::CLOUDINARY->value) {
                $deleted = $this->cloudinary::destroy($file->file_id);

                if (!$deleted) {
                    return [
                        'status' => false,
                        'message' => 'Failed to delete file from Cloudinary',
                        'data' => null,
                        'code' => 400,
                    ];
                }
            } else {
                if (!$this->s3->exists($file->path)) {
                    return [
                        'status' => false,
                        'message' => 'File does not exist in S3',
                        'data' => null,
                        'code' => 404,
                    ];
                }

                $deleted = $this->s3->delete($file->path);

                if (!$deleted) {
                    return [
                        'status' => false,
                        'message' => 'Failed to delete file from S3',
                        'data' => null,
                        'code' => 400,
                    ];
                }
            }

            $result = $this->fileService->delete($id);

            if (!$result) {
                return [
                    'status' => false,
                    'message' => 'Failed to delete file record',
                    'data' => null,
                    'code' => 400,
                ];
            }

            return [
                'status' => true,
                'message' => 'File successfully deleted',
                'data' => null,
                'code' => 200,
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage(),
                'data' => null,
                'code' => 400,
            ];
        }
    }

    public function removeFromCloud($id)
    {
        try {
            $file = $this->fileService->findById($id);

            if (!$file) {
                return [
                    'status' => false,
                    'message' => 'File not found',
                    'data' => null,
                    'code' => 404,
                ];
            }

            if ($file->provider == CloudTypeEnum::CLOUDINARY->value) {
                $deleted = $this->cloudinary::destroy($file->file_id);

                if (!$deleted) {
                    return [
                        'status' => false,
                        'message' => 'We cannot delete from cloud',
                        'data' => $deleted,
                        'code' => 400,
                    ];
                }

                return [
                    'status' => true,
                    'message' => 'File deleted',
                    'data' => $deleted,
                    'code' => 200,
                ];
            } else {
                if (!$this->s3->exists($file->path)) {
                    return [
                        'status' => false,
                        'message' => 'File not found in cloud',
                        'data' => null,
                        'code' => 404,
                    ];
                }

                $deleted = $this->s3->delete($file->path);

                if (!$deleted) {
                    return [
                        'status' => false,
                        'message' => 'We cannot delete from cloud',
                        'data' => $deleted,
                        'code' => 400,
                    ];
                }

                return [
                    'status' => true,
                    'message' => 'File deleted',
                    'data' => $deleted,
                    'code' => 200,
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage(),
                'data' => null,
                'code' => 400,
            ];
        }
    }

    public function update($id, $coming_file)
    {
        try {
            $file = $this->fileService->findById($id);

            if (!$file) {
                return [
                    'status' => false,
                    'message' => 'File not found',
                    'data' => null,
                    'code' => 404,
                ];
            }

            $delete_from_cloud = $this->removeFromCloud($id);

            if ($delete_from_cloud['status']) {
                if ($file->provider == CloudTypeEnum::S3->value) {
                    $path = $this->s3->put("{$file->folder}/" . now() . $file->owner_id, $coming_file);
                    $url = $this->s3->url($path);

                    if (!$path) {
                        return [
                            'status' => false,
                            'message' => 'Could not upload file on s3',
                            'data' => null,
                            'code' => 400,
                        ];
                    }
                    if (!$url) {
                        return [
                            'status' => false,
                            'message' => 'Path not found',
                            'data' => null,
                            'code' => 400,
                        ];
                    }

                    $updated = $this->fileService->update($file->id, [
                        'path' => $path,
                        'url' => $url,
                        'extension' => $this->extension($path),
                        'size' => $this->size($path),
                    ]);

                    return [
                        'status' => true,
                        'message' => 'File updated successfully',
                        'data' => $updated,
                        'code' => 201,
                    ];
                } else {
                    $uploaded = $this->cloudinary::upload($coming_file->getRealPath(), [
                        'folder' => $file->folder,
                    ]);

                    if (!$uploaded) {
                        return [
                            'status' => false,
                            'message' => 'We cannot upload the file',
                            'data' => $uploaded,
                            'code' => 400,
                        ];
                    }

                    $updated = $this->fileService->update($file->id, [
                        'url' => $uploaded->getSecurePath(),
                        'extension' => $uploaded->getExtension(),
                        'size' => $uploaded->getSize(),
                        'file_id' => $uploaded->getPublicId(),
                    ]);

                    return [
                        'status' => true,
                        'message' => 'File updated successfully',
                        'data' => $updated['data'],
                        'code' => 200,
                    ];
                }
            }

            return $delete_from_cloud;
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage(),
                'data' => null,
                'code' => 400,
            ];
        }
    }
}
