<?php

namespace Admin\ResourceTools\Videos;

use Exception;
use FFMpeg\Filters\Video\ExtractMultipleFramesFilter;
use GuzzleHttp\Client;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use InWeb\Admin\App\Actions\ActionEvent;
use InWeb\Admin\App\Http\Controllers\Controller;
use InWeb\Admin\App\Http\Requests\AdminRequest;
use InWeb\Admin\App\Http\Requests\ResourceDetailRequest;
use InWeb\Base\Entity;
use InWeb\Media\Images\Image;
use InWeb\Media\Videos\Video;
use InWeb\Media\Videos\WithVideos;

class VideosController extends Controller
{
    /**
     * @param ResourceDetailRequest $request
     * @return mixed
     */
    public function index(ResourceDetailRequest $request)
    {
        /** @var WithVideos|Entity $model */
        $model = $request->findModelOrFail();

        return [
            'videos' => VideoResource::collection(
                $model->videos()->with('images')->get()
            )
        ];
    }

    private function videoInfo(\App\Models\Video $video)
    {
        $video->refresh();

        return [
            'thumbnail'  => optional($video->image)->getUrl(),
            'thumbnails' => $video->images()->count() > 1 ? $video->images()->get()->map(function (Image $image) {
                return [
                    'original' => $image->getUrl(),
                    'clipped'  => $image->getUrl('thumbnail'),
                    'id'       => $image->id,
                ];
            }) : [],
        ];
    }

    /**
     * @param ResourceDetailRequest $request
     * @return mixed
     * @throws \Throwable
     */
    public function store(ResourceDetailRequest $request)
    {
        /** @var WithVideos|Entity $model */
        $model = $request->findModelOrFail();

        $videos = [];

        $inputVideos = $request->file('videos');
        $usingChunks = (bool) $request->input('usingChunks', false);

        $last = (bool) $request->input('isLast', false);

        if ($inputVideos) {
            foreach ($inputVideos as $video) {
                /** @var UploadedFile $video */
                if ($usingChunks) {
                    $video = $model->videos()->addChunked($video, $last, $video->getClientOriginalName());

                    if ($video)
                        $videos[] = $video;

                    if ($last) {
//                        $video->createFramesFromFile();
                    }
                } else {
                    $videos[] = $model->videos()->add($video, $video->getClientOriginalName());
                }
            }
        } else if ($url = $request->input('url')) {
            $video = $model->videos()->add($url);

            $videos[] = $video;

            $thumbnail = $video->getHostingThumbnailFromUrl($url);

            if ($thumbnail) {
                $video->images()->set($thumbnail);
            }
        }

        $this->actionEventForCreate($request->user(), $model, $videos)->save();

        return [
            'videos' => VideoResource::collection(collect($videos)),
        ];
    }

    /**
     * @param ResourceDetailRequest $request
     * @param \App\Models\Video $video
     * @return array
     * @throws \Throwable
     */
    public function refreshThumbnails(ResourceDetailRequest $request, \App\Models\Video $video)
    {
        $video->images->each(function(Image $image) {
            if (!$image->isMain())
                $image->remove();
        });

        $video->createFramesFromFile();

        return $this->videoInfo($video);
    }

    /**
     * @param ResourceDetailRequest $request
     * @param $resourceName
     * @param $resourceId
     * @param Video $video
     * @return mixed
     * @throws \Throwable
     */
    public function storeThumbnail(ResourceDetailRequest $request, $resourceName, $resourceId, $video)
    {
        /** @var WithVideos|Entity $model */
        $model = $request->findModelOrFail();

        /** @var \App\Models\Video $video */
        $video = $model->videos()->findOrFail($video);

        $images = collect([]);

        $inputImages = $request->file('images');

        if ($inputImages) {
            $count = count($video->images);

            foreach ($inputImages as $image) {
                /** @var UploadedFile $video */
                if ($count == 1) {
                    $images[] = $video->images()->set($image);
                } else {
                    /** @var Image $object */
                    $images[] = $object = $video->images()->add($image);
                    $object->setMain();
                }
            }
        }

        return $this->videoInfo($video);
    }

    /**
     * @param ResourceDetailRequest $request
     * @return array
     * @throws Exception
     */
    public function setMainThumbnail(ResourceDetailRequest $request)
    {
        /** @var WithVideos|Entity $model */
        $model = $request->findModelOrFail();

        /** @var \App\Models\Video $video */
        $video = $model->videos()->findOrFail($request->input('video'));

        $id = $request->input('image');

        $video->images->each(function (Image $image) use ($id) {
            if ($image->id == $id) {
                $image->setMain();
            }
        });

        return $this->videoInfo($video);
    }

    /**
     * @param ResourceDetailRequest $request
     * @throws Exception
     */
    public function destroy(ResourceDetailRequest $request)
    {
        /** @var WithVideos|Entity $model */
        $model = $request->findModelOrFail();

        $videosForDelete = $request->input('videos');

        $this->actionEventForDelete($request->user(), $model, $videosForDelete)->save();

        foreach ($videosForDelete as $video) {
            $model->videos()->remove((int) $video);
        }
    }

    /**
     * @param ResourceDetailRequest $request
     * @throws Exception
     */
    public function destroyThumbnail(ResourceDetailRequest $request)
    {
        /** @var WithVideos|Entity $model */
        $model = $request->findModelOrFail();

        $videosForDelete = $request->input('videos');

        foreach ($videosForDelete as $video) {
            $model->videos()->find((int) $video)->images()->removeAll();
        }
    }

    /**
     * @param Video $video
     * @throws Exception
     */
    public function main(Video $video)
    {
        $video->setMain();
    }

    /**
     * @param Authenticatable $user
     * @param Model $model
     * @param Video[] $videos
     * @return ActionEvent
     */
    public function actionEventForCreate($user, $model, $videos)
    {
        $original = $changes = [];

        foreach ($videos as $video) {
            $original['Video ' . $video->id] = '';
            $changes['Video ' . $video->id] = $video->filename;
        }

        return new ActionEvent([
            'batch_id'        => (string) Str::orderedUuid(),
            'user_id'         => $user->getAuthIdentifier(),
            'name'            => 'Video create',
            'actionable_type' => $model->getMorphClass(),
            'actionable_id'   => $model->getKey(),
            'target_type'     => $model->getMorphClass(),
            'target_id'       => $model->getKey(),
            'model_type'      => $model->getMorphClass(),
            'model_id'        => $model->getKey(),
            'fields'          => '',
            'original'        => $original,
            'changes'         => $changes,
            'status'          => 'finished',
            'exception'       => '',
        ]);
    }

    /**
     * @param Authenticatable $user
     * @param Model $model
     * @param array $videos
     * @return ActionEvent
     * @throws Exception
     */
    public function actionEventForDelete($user, $model, $videos)
    {
        $original = $changes = [];

        /** @var WithVideos $model */
        foreach ($videos as $video) {
            $video = $model->getVideo((int) $video);

            if (! $video)
                continue;

            $original['Video ' . $video->id] = $video->filename;
            $changes['Video ' . $video->id] = '';
        }

        return new ActionEvent([
            'batch_id'        => (string) Str::orderedUuid(),
            'user_id'         => $user->getAuthIdentifier(),
            'name'            => 'Video delete',
            'actionable_type' => $model->getMorphClass(),
            'actionable_id'   => $model->getKey(),
            'target_type'     => $model->getMorphClass(),
            'target_id'       => $model->getKey(),
            'model_type'      => $model->getMorphClass(),
            'model_id'        => $model->getKey(),
            'fields'          => '',
            'original'        => $original,
            'changes'         => $changes,
            'status'          => 'finished',
            'exception'       => '',
        ]);
    }

    public function changePositions(AdminRequest $request)
    {
        Video::updatePositionsById($request->input('videos'));
    }
}
