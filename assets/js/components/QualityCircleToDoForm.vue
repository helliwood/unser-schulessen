<template>
    <div id="quality-circle-todo-form">
        <div class="row">
            <div class="col text-right"><a v-if="answers.length > 0" href="javascript:void(0);" @click="send"
                                class="btn btn-secondary float-right">{{
                answers.length }}/3 To Do's erstellen</a></div>
        </div>
        <slot :toggleAnswer="toggleAnswer" :answers="answers"></slot>
        <a v-if="answers.length > 0" href="javascript:void(0);" @click="send" class="btn btn-secondary float-right">{{
            answers.length }}/3 To Do's erstellen</a>
        <b-modal ref="modal-warning"
                 @ok=""
                 :ok-only="true"
                 title="Warnung" ok-variant="warning" ok-title="Ok">
            <p class="my-4">Sie können maximal 3 Fragen auswahlen.</p>
        </b-modal>
        <b-modal ref="modal-ready"
                 @ok="send"
                 title="To Do fertigstellen" ok-variant="secondary" ok-title="To Do's erstellen"
                 cancel-title="Abbrechen" cancel-variant="primary-light">
            <p class="my-4">Sie haben die maximale Anzahl an Fragen ausgewählt. Möchten Sie die To Do's erstellen
                oder Fragen wechseln?</p>
        </b-modal>
    </div>
</template>

<script>
    import axios from 'axios'
    import qs from 'qs';

    axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

    export default {
        name: "QualityCircleToDoForm",
        data() {
            return {
                answers: []
            }
        },
        methods: {
            toggleAnswer(id) {
                if (this.answers.includes(id)) {
                    this.answers.splice(this.answers.indexOf(id), 1);
                } else {
                    if (this.answers.length >= 3) {
                        this.$refs['modal-warning'].show();
                    } else {
                        this.answers.push(id);
                        if (this.answers.length == 3) {
                            this.$refs['modal-ready'].show();
                        }
                    }
                }
            },
            send() {
                axios.post('', qs.stringify({answers: this.answers})).then((result) => {
                    location.href = result.data.redirect;
                }).catch((error) => {
                    console.log(error);
                    console.log(error.response.data.message);
                });
            }
        }
    }
</script>

<style scoped>

</style>
