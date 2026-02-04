<template>
    <div>
        <slot></slot>
    </div>
</template>

<script>
    import axios from 'axios'

    axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

    export default {
        name: "qc-input",
        props: {
            question_id: Number
        },
        data() {
            return {
                timeoutId: null,
                className: ''
            }
        },
        mounted() {
            console.log("qc-input", this.$el);
            this.className = this.$el.querySelector('input[type=number]').className;
            this.$el.querySelector('input[type=number]').addEventListener("change", this.qc_change);
            this.$el.querySelector('input[type=number]').dispatchEvent(new Event('change'));
        },
        methods: {
            qc_change: function (event) {
                var self = this;
                if (isNaN(event.target.valueAsNumber)) {
                    event.target.className = self.className;
                } else {
                    console.log("qc_change", self.question_id, event.target.valueAsNumber);
                    clearTimeout(self.timeoutId);
                    self.timeoutId = setTimeout(function () {
                        axios.get('/quality_check/check/' + self.question_id + '/' + event.target.valueAsNumber).then((data) => {
                            console.log(data);
                            event.target.className = self.className + ' ' + data.data;
                        }).catch(error => {

                        });
                    }, 500);
                }
            }
        }
    }
</script>

<style scoped>
    .true {
        border-color: #04B100;
        border-width: 2px;
    }

    .partial {
        border-color: #FFA700;
        border-width: 2px;
    }

    .false {
        border-color: #D30000;
        border-width: 2px;
    }
</style>
