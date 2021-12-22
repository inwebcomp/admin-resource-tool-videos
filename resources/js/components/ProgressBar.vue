<template>
    <div class="video-gallery__element__progress" v-if="value">
        <div class="video-gallery__element__progress__value">{{ Math.round(value) }}%</div>
        <div class="video-gallery__element__progress__loaded" :style="{width: value + '%'}"></div>
    </div>
</template>

<script>
    export default {
        name: "ProgressBar",

        props: {
            progress: {
                type: Number,
                default: null,
            }
        },

        computed: {
            value() {
                if (this.progress > 1)
                    return 100;

                return this.progress * 100
            }
        }
    }
</script>