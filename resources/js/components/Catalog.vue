<template>
    <draggable v-bind="dragOptions" :value="videos" @input="$emit('changePositions', $event)" class="video-gallery__catalog flex flex-wrap">

        <video-element :thumbnail="video.thumbnail"
                       :otherThumbnails="video.thumbnails"
                       :loadingThumbnails="video.loadingThumbnails"
                       class="mx-2"
                       v-for="(video, $i) in videos" :key="$i"
                       @remove="$emit('remove', $i)"
                       @setMain="$emit('setMain', $i)"
                       @loadThumbnail="$emit('loadThumbnail', $event, $i)"
                       @removeThumbnail="$emit('removeThumbnail', $i)"
                       @setMailThumbnail="$emit('setMailThumbnail', $event, $i)"
                       @refreshThumbnails="$emit('refreshThumbnails', $i)"
                       :main="video.main">

            <video v-if="! video.isEmbed" class="video-gallery__catalog__video" width="320" height="240" controls preload="metadata">
                <source :src="video.embed" :type="video.mimeType">
                {{ __("Ваш браузер не поддерживает video тег") }}
            </video>

            <iframe v-if="video.isEmbed" class="video-gallery__catalog__video" width="16rem" height="12rem" :src="video.embed" frameborder="0"
                    allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen></iframe>

        </video-element>
    </draggable>
</template>

<script>
    import VideoElement from "./VideoElement"
    import Draggable from 'vuedraggable'

    export default {
        name: "Catalog",
        components: {VideoElement,Draggable},

        props: {
            videos: {},
        },

        computed: {
            dragOptions() {
                return {
                    delay: 0,
                    touchStartThreshold: 0,
                    forceFallback: true,
                    animation: 150,
                    ghostClass: "ghost",
                    dragClass: "sortable-drag",
                }
            }
        }
    }
</script>

<style>
    .gallery__catalog .sortable-drag {
        opacity: 1 !important;
        visibility: visible;
    }

    .gallery__catalog .ghost {
        opacity: 0 !important;
        visibility: hidden;
    }
</style>