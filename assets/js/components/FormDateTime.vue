<template>
  <div>
    <datetime :disabled="disabled ? true : false" input-class="form-control" type="datetime" format="dd. LLLL yyyy HH:mm"
              :hidden-name="name" v-model="datetime">1
      <template slot="after">
        <button v-if="!disabled" class="btn btn-danger float-right"
                style="border-top-left-radius: 0; border-bottom-left-radius: 0;" type="button"
                @click="datetime=null;"><i class="fas fa-trash-alt"></i>
        </button>
      </template>
    </datetime>
  </div>
</template>

<script>
export default {
  name: "form-date-time",
  props: {
    name: String,
    value: String,
    disabled: Boolean
  },
  data() {
    return {
      datetime: null
    }
  },
  mounted() {
    console.log(this.disabled);
    this.datetime = this.value ? this.value + 'Z' : null;
  }
}
</script>

<style scoped>
button {
  position: absolute;
  right: 15px;
  top: 0px;
}
</style>
