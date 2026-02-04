<template>
    <span>
        <b-modal id="modal-skip" title="Qualitätsfeld überspringen"
                 @ok="go"
                 ok-variant="warning"
                 ok-title="Qualitätsfeld überspringen"
                 cancel-title="Abbrechen" cancel-variant="primary-light">
            <p class="">Möchten Sie das Qualitätsfeld "{{ category }}" wirklich
                überspringen? Es werden alle Fragen auf "nicht beantwortet" gesetzt.</p>
            <p class="mb-0"><label><input type="checkbox" id="skipModal" @change="change"/> <em>Nicht mehr
                anzeigen</em></label></p>
        </b-modal>
        <a href="javascript:void(0);" class="btn-outline-primary btn mr-2 order-2"
           v-b-modal.modal-skip>Überspringen</a>
    </span>
</template>

<script>
    export default {
        name: "quality-check-skip-button",
        props: {
            url: null,
            category: null
        },
        data() {
            return {
                checked: false
            }
        },
        mounted() {
        },
        methods: {
            go: function () {
                location.href = this.url + '?hideModal=' + (this.checked ? "1" : "0");
            },
            change: function (e) {
                console.log(e.target.checked);
                this.checked = e.target.checked;
            }
        }
    }
</script>

<style scoped>

</style>
