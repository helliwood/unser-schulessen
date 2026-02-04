<template>
    <fieldset class="form-group position-relative">
        <div class="d-flex">
            <slot name="label"></slot>
            <div class="py-2">
                <a href="javascript:void(0);" class="btn btn-primary btn-sm text-nowrap" v-if="!this.edit" @click="change">Bearbeiten</a>
                <a href="javascript:void(0);" class="btn btn-danger btn-sm text-nowrap" v-if="this.edit" @click="change">Nicht
                    bearbeiten</a>
            </div>
        </div>
        <div class="row" v-if="edit">
            <slot name="form"></slot>
        </div>
        <input v-if="!this.edit" type="hidden" name="clear_category[]" :value="id"/>
    </fieldset>
</template>

<script>
    export default {
        name: "quality-check-sub-category",
        props: {
            id: null,
            open: {
                type: Boolean,
                default: false
            }
        },
        data() {
            return {
                edit: false
            }
        },
        mounted() {
            if (this.open) {
                this.edit = true;
            }
            console.log("quality-check-sub-category", this.$el);
        },
        methods: {
            change: function () {
                this.edit = !this.edit;
            }
        }
    }
</script>

<style scoped>

</style>
