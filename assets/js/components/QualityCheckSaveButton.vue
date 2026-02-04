<template>
  <span>
    <b-button @click="showModal" variant="secondary">Speichern und schließen</b-button>

    <b-modal v-model="modalShow" hide-header-close hide-header hide-footer>
      <h3>Was möchten Sie tun?</h3>
      <b-button type="submit" variant="primary" onclick="getElementById('save').click()" class="mb-1 ">
        Zwischenstand speichern und später fortfahren
      </b-button>
      <b-button type="submit" variant="warning" onclick="getElementById('finalise').click()">
        Qualitäts-Check abschließen/finalisieren
      </b-button>
    </b-modal>
  </span>
</template>

<script>
export default {
  name: 'QualityCheckSaveButton',
  data() {
    return {
      modalShow: false
    };
  },
  methods: {
    showModal() {
      this.modalShow = true;
    },
  }
}
</script>
