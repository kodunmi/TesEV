<?php

namespace App\Repositories\Core;

use App\Interfaces\Core\FileRepositoryInterface;
use App\Models\File;
use Exception;

class FileRepository implements FileRepositoryInterface
{
    public function create(array $data)
    {
        try {
            $file = new File();

            $file->public_id = uuid();
            $file->owner_id = $data['owner_id'] ?? null;
            $file->type = $data['type'] ?? null;
            $file->name = $data['name'] ?? null;
            $file->number = $data['number'] ?? null;
            $file->url = $data['url'] ?? null;
            $file->size = $data['size'] ?? null;
            $file->file_id = $data['file_id'] ?? null;
            $file->provider = $data['provider'] ?? 's3';
            $file->path = $data['path'] ?? null;
            $file->extension = $data['extension'] ?? null;
            $file->folder = $data['folder'] ?? null;

            $file->save();

            return $file;
        } catch (\Exception $e) {
            throw new Exception('Error creating file' . $e->getMessage());
        }
    }

    public function update(string $id, array $data)
    {
        try {
            $file = $this->findById($id);

            if ($file) {
                $fieldsToUpdate = [
                    'owner_id',
                    'type',
                    'name',
                    'number',
                    'url',
                    'size',
                    'file_id',
                    'provider',
                    'path',
                    'extension',
                    'folder',
                ];

                foreach ($fieldsToUpdate as $field) {
                    if (array_key_exists($field, $data)) {
                        $file->$field = $data[$field];
                    }
                }
                $file->save();

                return [
                    'status' => true,
                    'message' => 'File updated successfully',
                    'data' => $file,
                    'code' => '',
                ];
            }

            return [
                'status' => false,
                'message' => 'File not found',
                'data' => $file,
                'code' => '',
            ];
        } catch (\Exception $e) {
            throw new Exception('Error updating file' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        return File::destroy($id);
    }

    public function findById($id)
    {
        return File::find($id);
    }

    public function findByPath($path)
    {
        return File::where('path', $path)->first();
    }

    public function findByName($name)
    {
        return File::where('name', $name)->first();
    }

    public function findByURL($url)
    {
        return File::where('url', $url)->first();
    }

    public function all()
    {
        return File::all();
    }

    public function query()
    {
        return File::query();
    }
}
