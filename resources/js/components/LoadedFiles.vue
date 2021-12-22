<template>
    <div class="gallery__loaded-files flex flex-wrap -mx-2">
        <element-for-load class="mx-2 mb-4" v-for="(video, $i) in videos" :key="$i" :loading="video.loading" :progress="video.progress" :errors="video.errors" @remove="$emit('remove', $i)" @upload="$emit('upload', $i)">

            <div class="p-4 pb-8 text-center" v-if="video.isLocal">{{ video.file_name }}</div>

            <iframe v-if="! video.isLocal" class="video-gallery__catalog__video" width="16rem" height="12rem" :src="video.embed" frameborder="0"
                    allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen></iframe>

        </element-for-load>

        <hr class="gallery__hr mt-2 mb-6">
    </div>
</template>

<script>
    import ElementForLoad from "./ElementForLoad"

    export default {
        name: "LoadedFiles",
        components: {ElementForLoad},

        props: {
            videos: {},
        }
    }
</script>