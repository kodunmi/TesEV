<?php

namespace App\Enum;


enum CloudTypeEnum: string
{
    case S3 = "s3";
    case CLOUDINARY = 'cloudinary';
}
