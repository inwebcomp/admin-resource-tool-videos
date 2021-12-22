<?php

namespace Admin\ResourceTools\Videos;

use Illuminate\Http\Resources\Json\JsonResource;
use InWeb\Media\Images\Image;
use InWeb\Media\Videos\Video;

class VideoResource extends JsonResource
{
    public static $wrap = false;

    public function toArray($request)
    {
        /** @var Video $this */
        return [
            'id'                => $this->id,
            'url'               => $this->getUrl(),
            'embed'             => $this->getUrl(true),
            'isEmbed'           => $this->isEmbed(),
            'mimeType'          => $this->mimeType,
            'main'              => $this->isMain(),
            'loadingThumbnails' => false,
            'thumbnail'         => optional($this->image)->getUrl(),
            'thumbnails'        => $this->images->count() > 1 ? $this->images->map(function (Image $image) {
                return [
                    'original' => $image->getUrl(),
                    'clipped'  => $image->getUrl('thumbnail'),
                    'id'       => $image->id,
                ];
            }) : [],
        ];
    }
}
