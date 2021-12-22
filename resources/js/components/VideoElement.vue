<template>
    <component :is="zoom ? 'a' : 'div'" :href="zoom" target="_blank" class="video-gallery__element video-gallery__element--video border-t py-4">
        <div class="video-gallery__handle px-6 cursor-move handle text-grey select-none items-center flex">
            <i class="icon icon--handle"></i>
        </div>

        <div class="video-gallery__video-container mr-4">
            <slot></slot>
        </div>

        <image-upload-field class="mr-auto"
                            :label="__('Превью') + ':'"
                            :value="thumbnail"
                            v-slot="{image}"
                            :maxSize="6"
                            :accept="'image/jpeg,image/png'"
                            @input="$emit('loadThumbnail', $event)"
                            @clear="$emit('removeThumbnail')">
            <img :src="image" v-if="image" style="height: 7.5rem" alt="">
        </image-upload-field>

        <div class="video-gallery__element__thumbnails px-2">
            <div class="form__group__label mt-0"><span v-if="otherThumbnails && otherThumbnails.length">{{ __('Все') }}:</span> <i class="fas fa-refresh text-grey-light cursor-pointer ml-2 mr-auto hover:text-accent" @click="$emit('refreshThumbnails')"></i><span v-if="loadingThumbnails" class="font-normal ml-4 mr-auto">{{ __('Загрузка ...') }}</span></div>

            <div class="flex flex-wrap" v-if="otherThumbnails && otherThumbnails.length">
                <img v-for="image in otherThumbnails" :src="image.clipped" v-if="image.clipped" class="block mr-1 mb-1 border border-grey cursor-pointer hover:border-accent" :class="{'border-accent': image.original == thumbnail}" style="width: 95px;" :title="__('Установить')" alt="" @click="$emit('setMailThumbnail', image.id)">
            </div>
        </div>

        <div class="flex">
            <div class="video-gallery__element__main" :class="{'gallery__element__main--active': main}" :title="__('Главное видео')" @click.prevent="! main && $emit('setMain')">
                <i class="fa-star" :class="main ? 'fas' : 'far'"></i>
            </div>
            <div class="video-gallery__element__remove text-grey hover:text-danger cursor-pointer" @click.stop.prevent="$emit('remove')">
                <i class="far fa-trash-alt"></i>
            </div>
        </div>
    </component>
</template>

<script>
    export default {
        name: 'ImageElement',

        props: {
            main: {},
            zoom: {},
            thumbnail: {},
            otherThumbnails: {},
            loadingThumbnails: {},
        },
    }
</script>
