<template>
    <div class="video-gallery">
        <load-area @load="load" class="mb-4" :max-size="field.maxSize" :accept="field.accept"
                   :multiple="field.multiple"/>
        <div class="text-center my-4">{{ __('или') }}</div>
        <url-area @load="uploadViaUrl" class="mb-4"/>

        <loaded-files v-if="loadedFiles.length" :videos="loadedFiles" @remove="removeLoaded"></loaded-files>

        <form-section class="-mx-1" :field="{name: __('Добавленные видео')}"></form-section>

        <catalog class="-mx-2"
                 :videos="videos"
                 @remove="remove"
                 @loadThumbnail="loadThumbnail"
                 @removeThumbnail="removeThumbnail"
                 @setMailThumbnail="setMailThumbnail"
                 @refreshThumbnails="refreshThumbnails"
                 @changePositions="changePositions"
                 @setMain="setMain"/>
    </div>
</template>

<script>
    import LoadArea from "./LoadArea"
    import UrlArea from "./UrlArea"
    import LoadedFiles from "./LoadedFiles"
    import Catalog from "./Catalog"

    export default {
        components: {
            Catalog,
            LoadedFiles,
            LoadArea,
            UrlArea,
        },

        props: {
            resourceName: {},
            resourceId: {},
            field: {},
        },

        data() {
            return {
                loadedFiles: [],
                videos: [],
                loading: false,
                loadingRemove: false,
                loadingSetMain: false,
            }
        },

        created() {
            this.fetch()
        },

        methods: {
            fetch() {
                App.api.request({
                    url: 'resource-tool/videos/' + this.resourceName + '/' + this.resourceId,
                }).then(({videos}) => {
                    this.videos = videos
                })
            },

            removeLoaded(index) {
                this.loadedFiles = this.loadedFiles.filter((value, i) => i !== index)
            },

            load(files, uploadCallback) {
                Array.from(files).forEach(file => {
                    let errors = file.errors

                    file = new File([file], file.name, {type: file.type})

                    const fileData = {
                        file: file,
                        name: file.name,
                        file_name: file.name,
                        errors: errors,
                        progress: 0,
                        loading: false,
                        loadingThumbnails: false,
                        isLocal: true,
                        sending: false,
                        chunks: [],
                        size: file.size,
                        chunksLoaded: 0,
                    }

                    if (this.field.multiple) {
                        this.loadedFiles.push(fileData)
                    } else {
                        this.loadedFiles = [fileData]
                    }
                })

                if (!uploadCallback)
                    uploadCallback = this.upload

                uploadCallback()
            },

            upload(uploadCallback) {
                if (!this.loadedFiles.length) {
                    this.loading = false
                    return
                }

                if (this.loading)
                    return

                this.loading = true

                let files = []

                this.loadedFiles.forEach(file => {
                    if (file.errors.length) {
                        file.loading = false
                        return
                    }

                    file.loading = true

                    files.push(file)
                })

                if (!uploadCallback) {
                    this.uploadRequest(files)
                } else {
                    uploadCallback(files)
                }
            },

            uploadRequest(files) {
                files = files.filter(file => !file.sending || file.chunks.length)

                if (!files.length)
                    return

                let formData = new FormData()

                formData.append('_method', 'PUT')

                files.forEach(file => {
                    if (this.field.chunks) {
                        if (! file.sending) {
                            file.chunks = this.createChunks(file.file)
                        }

                        formData.set('usingChunks', "1");
                        formData.set('isLast', file.chunks.length === 1 ? "1" : "0");
                        formData.append('videos[]', file.chunks[0], file.file.name + '.part')
                    } else {
                        formData.append('videos[]', file.file)
                    }

                    file.sending = true
                })

                App.api.request({
                    method: 'POST',
                    url: 'resource-tool/videos/' + this.resourceName + '/' + this.resourceId,
                    data: formData,
                }, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                    },
                    onUploadProgress: (progressEvent) => {
                        if (this.field.chunks) {
                            files.forEach(file => {
                                file.progress = (file.chunksLoaded * this.field.chunks + progressEvent.loaded) / file.size
                            })
                        } else {
                            files.forEach(file => file.progress = progressEvent.loaded / progressEvent.total)
                        }
                    }
                }).then(({videos}) => {
                    this.loading = false

                    let hasOnlyChunks = true

                    files.forEach(file => {
                        if (file.chunks.length > 0) {
                            file.chunks.shift()
                            file.chunksLoaded++
                        } else {
                            hasOnlyChunks = false
                        }

                        this.loadedFiles = this.loadedFiles.filter(value => value.name !== file.name || file.chunks.length > 0)
                    })

                    this.videos.push(...videos)

                    this.upload()

                    if (! hasOnlyChunks) {
                        App.$emit('videoUploaded', files)
                        App.$emit('indexRefresh')

                        this.$toasted.show(
                            videos.length > 1 ? this.__('The videos was uploaded!') : this.__('The video was uploaded!'),
                            {type: 'success'},
                        )
                    }
                }).catch(() => {
                    this.loading = false
                })
            },

            uploadThumbnailRequest(files, video) {
                files = files.filter(file => !file.sending)

                if (!files.length)
                    return

                let formData = new FormData()

                files.forEach(file => {
                    formData.append('images[]', file.file)
                    file.sending = true
                })

                App.api.request({
                    method: 'POST',
                    url: 'resource-tool/videos/' + this.resourceName + '/' + this.resourceId + '/' + video.id + '/thumbnail',
                    data: formData,
                }, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    },
                }).then(({thumbnail, thumbnails}) => {
                    App.$emit('videoThumbnailUploaded', files)

                    this.loading = false

                    files.forEach(file => {
                        this.loadedFiles = this.loadedFiles.filter(value => value.name !== file.name)
                    })

                    video.thumbnail = thumbnail
                    video.thumbnails = thumbnails

                    this.$toasted.show(
                        this.__('The video thumbnail was uploaded!'),
                        {type: 'success'},
                    )
                }).catch(() => {
                    this.loading = false
                })
            },

            loadThumbnail(files, index) {
                let video = this.videos.find((value, i) => i === index)

                let requestCallback = (files) => {
                    this.uploadThumbnailRequest(files, video)
                }

                let uploadCallback = (files) => {
                    this.upload(requestCallback)
                }

                this.load(files, uploadCallback)
            },

            uploadViaUrl(url) {
                if (!url)
                    return

                App.api.request({
                    method: 'PUT',
                    url: 'resource-tool/videos/' + this.resourceName + '/' + this.resourceId,
                    data: {
                        url,
                    },
                }).then(({videos}) => {
                    App.$emit('videoUploaded', url)
                    App.$emit('indexRefresh')

                    this.loading = false

                    this.videos.push(...videos)

                    this.$toasted.show(
                        videos.length > 1 ? this.__('The videos was uploaded!') : this.__('The video was uploaded!'),
                        {type: 'success'},
                    )
                }).catch(() => {
                    this.loading = false
                })
            },

            remove(index) {
                if (this.loadingRemove)
                    return

                if (!confirm(this.__('Are you sure to delete the video?')))
                    return

                this.loadingRemove = true

                let video = this.videos.find((value, i) => i === index)
                video.loading = true

                App.api.request({
                    method: 'DELETE',
                    url: 'resource-tool/videos/' + this.resourceName + '/' + this.resourceId,
                    data: {
                        videos: [video.id],
                    },
                }).then(() => {
                    this.loadingRemove = false

                    App.$emit('videoRemoved', video)
                    App.$emit('indexRefresh')

                    this.videos = this.videos.filter((value, i) => i !== index)

                    if (video.main && this.videos.length)
                        this.videos[0].main = true

                    this.$toasted.show(
                        this.__('The video was removed!'),
                        {type: 'success'},
                    )
                }).catch(() => {
                    this.loadingRemove = false
                })
            },

            removeThumbnail(index) {
                if (this.loadingRemove)
                    return

                if (!confirm(this.__('Are you sure to delete video thumbnail?')))
                    return

                this.loadingRemove = true

                let video = this.videos.find((value, i) => i === index)
                video.loading = true

                App.api.request({
                    method: 'DELETE',
                    url: 'resource-tool/videos/' + this.resourceName + '/' + this.resourceId + '/thumbnail',
                    data: {
                        videos: [video.id],
                    },
                }).then(() => {
                    this.loadingRemove = false

                    App.$emit('videoThumbnailRemoved', video)

                    this.videos.find((value, i) => i == index).thumbnail = false

                    this.$toasted.show(
                        this.__('The video thumbnail was removed!'),
                        {type: 'success'},
                    )
                }).catch(() => {
                    this.loadingRemove = false
                })
            },

            refreshThumbnails(index) {
                let video = this.videos.find((value, i) => i === index)

                if (video.loadingThumbnails)
                    return

                video.loadingThumbnails = true

                App.api.request({
                    method: 'POST',
                    url: 'resource-tool/videos/thumbnail/refresh/' + video.id,
                }).then(({thumbnail, thumbnails}) => {
                    video.loadingThumbnails = false

                    video.thumbnail = thumbnail
                    video.thumbnails = thumbnails
                    video.loading = false

                    this.$toasted.show(
                        this.__('The video thumbnails were recreated!'),
                        {type: 'success'},
                    )
                }).catch(() => {
                    video.loadingThumbnails = false
                })
            },

            setMailThumbnail(value, index) {
                if (this.loadingSetMain)
                    return

                this.loadingSetMain = true

                let video = this.videos.find((value, i) => i === index)
                video.loading = true

                App.api.request({
                    method: 'POST',
                    url: 'resource-tool/videos/' + this.resourceName + '/' + this.resourceId + '/thumbnail/main',
                    data: {
                        video: video.id,
                        image: value,
                    },
                }).then(({thumbnail, thumbnails}) => {
                    this.loadingSetMain = false

                    video.thumbnail = thumbnail
                    video.thumbnails = thumbnails
                    video.loading = false

                    this.$toasted.show(
                        this.__('The video thumbnail was set!'),
                        {type: 'success'},
                    )
                }).catch(() => {
                    this.loadingSetMain = false
                })
            },

            setMain(index) {
                if (this.loadingSetMain)
                    return

                this.loadingSetMain = true

                let video = this.videos.find((value, i) => i === index)
                video.loading = true

                App.api.request({
                    method: 'PUT',
                    url: 'resource-tool/videos/main/' + video.id,
                }).then(() => {
                    this.loadingSetMain = false

                    App.$emit('videoSetMain', video)
                    App.$emit('indexRefresh')

                    this.videos.forEach((value, i) => value.main = i === index)

                    this.$toasted.show(
                        this.__('The video was set as main!'),
                        {type: 'success'},
                    )
                }).catch(() => {
                    this.loadingSetMain = false
                })
            },

            changePositions(videos) {
                this.videos = videos

                if (this.loading)
                    return

                this.loading = true

                App.api.request({
                    method: 'POST',
                    url: 'resource-tool/videos/' + this.resourceName + '/' + this.resourceId + '/positions',
                    data: {
                        videos: this.videos.map(video => video.id),
                    },
                }).then(() => {
                    this.loading = false

                    App.$emit('videoChangePositions')

                    this.$toasted.show(
                        this.__('Videos positions were changed'),
                        {type: 'success'},
                    )
                }).catch(() => {
                    this.loading = false
                })
            },

            createChunks(file) {
                let size = this.field.chunks, chunksCount = Math.ceil(file.size / size), chunks = [];

                for (let i = 0; i < chunksCount; i++) {
                    chunks.push(file.slice(
                        i * size, Math.min(i * size + size, file.size), file.type
                    ));
                }

                return chunks
            }
        },
    }
</script>
