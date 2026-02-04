<template>
    <div>
        <slot></slot>
    </div>
</template>

<script>
    export default {
        name: "form-manipulator",
        props: {
            manipulations: Array
        },
        data() {
            return {}
        },
        mounted() {
            console.log("i'm here", this.$el);
            var self = this;
            this.manipulations.forEach((man) => {
                console.log(man);
                self.$el.querySelectorAll(man.watch).forEach((watch) => {
                    let value;
                    if (watch.type == 'radio') {
                        value = self.$el.querySelector(man.watch + ':checked') ? self.$el.querySelector(man.watch + ':checked').value : null;
                    } else {
                        value = watch.value;
                    }
                    if (man.if[value]) {
                        self.$el.querySelectorAll(man.if[value].elem).forEach((elem) => {
                            self.manipulateElem(elem, man.if[value]);
                        });
                    }
                    watch.addEventListener("change", function () {
                        if (man.if[this.value]) {
                            let option = man.if[this.value];
                            self.$el.querySelectorAll(option.elem).forEach((elem) => {
                                self.manipulateElem(elem, option);
                            });
                        }
                    });
                });
            });
        },
        methods: {
            manipulateElem: function (elem, option) {
                if (option.style) {
                    for (let style in option.style) {
                        elem.style[style] = option.style[style];
                    }
                }
            }
        }
    }
</script>

<style scoped>

</style>
