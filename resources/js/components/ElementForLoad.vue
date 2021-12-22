<template>
    <div class="gallery__element gallery__element--for-load gallery__image" :class="{'gallery__element--error': errors.length}">
        <slot></slot>

        <div class="gallery__element__overlay" @click="act">
            <svg v-if="! loading && ! errors.length" class="gallery__load-area__icon w-8 h-8" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                <path d="M16.88 9.1A4 4 0 0 1 16 17H5a5 5 0 0 1-1-9.9V7a3 3 0 0 1 4.52-2.59A4.98 4.98 0 0 1 17 8c0 .38-.04.74-.12 1.1zM11 11h3l-4-4-4 4h3v3h2v-3z" />
            </svg>

            <i class="fas fa-spinner-third gallery__element__loading" v-if="loading"></i>

            <div class="gallery__element__error" v-for="(error) in errors">{{ error }}</div>
            <progress-bar :progress="progress"/>

            <div class="gallery__element__remove" @click.stop="$emit('remove')">
                <i class="far fa-times"></i>
            </div>
        </div>
    </div>
</template>

<script>
    import ProgressBar from "./ProgressBar"
    export default {
        name: 'ElementForLoad',
        components: {ProgressBar},
        props: {
            loading: {
                type: Boolean,
                default: false
            },
            progress: {
                type: Number,
                default: null
            },
            errors: {
                type: Array,
                default: () => {}
            },
        },

        methods: {
            act() {
               if (this.errors.length) {
                   this.$emit('remove')
                   return
               }

               this.$emit('upload')
            }
        },
    }
</script>
