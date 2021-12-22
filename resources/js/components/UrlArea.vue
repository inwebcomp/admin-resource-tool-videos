<template>
    <form @submit.prevent="$emit('load', url)" class="gallery__url-area flex">
        <text-input class="flex-1 mr-2" v-model="url" :placeholder="__('Вставьте ссылку на видео')"></text-input>
        <app-button submit class="gallery__url-area__button">
            <i class="fa fas fa-upload mr-2"></i>
            {{ __('Загрузить') }}
        </app-button>
    </form>
</template>

<script>
    export default {
        name: "UrlArea",

        data() {
            return {
                url: '',
            }
        },

        created() {
            App.$on('imageUploaded', (image) => {
                if (typeof image === 'string')
                    this.url = ''
            })
        }
    }
</script>

<style>
    .gallery__url-area__button {
        height: 3rem;
        line-height: 2.9rem;
    }
</style>