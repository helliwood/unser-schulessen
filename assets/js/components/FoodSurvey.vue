<template>
  <div class="row" id="food-survey-creator">
    <div class="col" v-if="loading">
      <b-spinner type="border" small></b-spinner>
      Lade Daten, bitte warten...
    </div>
    <div class="col" v-else>
      <b-card no-body>
        <b-tabs v-model="tab" card>
          <b-tab title="Allgemein">
            <div class="form-group row">
              <label for="name" class="col-sm-3 col-form-label">Name:</label>
              <div class="col-sm-9">
                <input id="name" maxlength="150" class="form-control" type="text" v-model="formData.name"/>
                <small class="form-text text-muted">Der Name darf aus maximal 150 Zeichen bestehen.</small>
              </div>
            </div>
            <div class="form-group row">
              <label for="image_file" class="col-sm-3 col-form-label">Bild:</label>
              <div class="col-sm-9">
                <div class="custom-file">
                  <input id="image_file" type="file" @change="onFileChange" lang="de" class="custom-file-input">
                  <label for="image_file" class="custom-file-label"></label>
                  <small class="form-text text-muted" v-if="currentId">ACHTUNG: Wenn Sie das Bild austauschen, werden
                    alle
                    Spots gelöscht!</small>
                </div>
              </div>
            </div>
            <div class="form-group row">
              <div class="col-sm-3"></div>
              <div class="col-sm-9">
                <div v-if="formError" class="error"><strong>Fehler:</strong> {{ formError }}</div>
                <button class="btn-primary btn mt-3" @click="save">Speichern</button>
              </div>
            </div>
          </b-tab>
          <b-tab title="Spots setzen" :disabled="!currentId" class="position-relative">
            <div class="disable-konva" v-if="disableKonva">
              <b-spinner type="border" variant="white"></b-spinner>
            </div>
            <button class="btn" :class="activatePainting ? 'btn-primary' :'btn-outline-primary'"
                    @click="activatePainting = !activatePainting"><i
                class="fa fa-plus"></i> Spot hinzufügen
            </button>
            <button class="btn" @click="downloadImage">Download</button>
            <button @click="home()" class="btn btn-primary float-right">Speichern</button>
            <div class="position-relative mt-3">
              <div id="stage-parent" ref="stageParent">
                <div id="container" ref="container"></div>
              </div>
              <img ref="image" class="img-fluid"/>
            </div>
            <h2 class="mt-2">Spots</h2>
            <table class="table table-bordered table-hover table-striped">
              <thead>
              <tr>
                <th>#</th>
                <th>Name</th>
                <th style="width: 250px;">Optionen</th>
              </tr>
              </thead>
              <tbody>
              <tr v-for="(spot, index) in foodSurvey.spots" @click="selectSpot(spot)" :key="spot.order"
                  :class="{'table-primary':selectedSpotId === spot.id.toString()}">
                <td>{{ spot.order }}</td>
                <td><input type="text" class="form-control" maxlength="150" @change="saveSpot(spot)"
                           v-model="spot.name"/>
                </td>
                <td>
                  <button v-if="spot.order > 1" @click="spotUp(spot)" class="btn btn-info"><i
                      class="fa fa-arrow-up"></i></button>
                  <button v-if="spot.order < foodSurvey.spots.length" @click="spotDown(spot)" class="btn btn-info"><i
                      class="fa fa-arrow-down"></i></button>
                  <button @click="removeSpot(spot.id)" class="btn btn-danger"><i class="fa fa-trash"></i> Spot
                    löschen
                  </button>
                </td>
              </tr>
              </tbody>
            </table>
            <button @click="home()" class="btn btn-primary float-right mb-3">Speichern</button>
          </b-tab>
        </b-tabs>
      </b-card>
    </div>
  </div>
</template>

<script>
import axios from "axios";
import Konva from "konva";

export default {
  name: "food-survey",
  props: {
    id: {
      type: Number,
      default: null
    },
    loadUri: String,
    uploadUri: String,
    saveSpotUri: String,
    homeUri: String,
    removeSpotUri: String
  },
  data() {
    return {
      currentId: null,
      loading: true,
      tab: 0,
      formData: {id: null, name: null, data: null, selectedFile: null},
      formError: null,
      foodSurvey: {spots: []},
      strokeWidth: 10,
      stage: null,
      activatePainting: false,
      isPainting: false,
      imageWidth: null,
      imageHeight: null,
      transformer: null,
      saveTimeout: null,
      disableKonva: false,
      selectedSpotId: null
    }
  },
  mounted() {
    this.currentId = this.id;
    if (this.currentId) {
      this.loadFoodSurvey(this.currentId).then(res => {
        this.loading = false;
        this.tab = 1;
      });
    } else {
      this.loading = false;
    }
  },
  watch: {
    tab: function (value) {
      console.log(value);
      if (value === 1) {
        this.$nextTick(() => {
          this.initDrawing();
        });
      }
    }
  },
  methods: {
    loadFoodSurvey: function (id) {
      let self = this;
      return new Promise((resolve, reject) => {
        axios.post(this.loadUri + '/' + id).then(res => {
          self.foodSurvey = res.data;
          self.formData.id = self.foodSurvey.id;
          self.formData.name = self.foodSurvey.name;
          resolve(res.data);
        }).catch(err => {
          console.log(err.response.data);
          reject(err.response.data.detail);
        });
      });
    },
    onFileChange: function (event) {
      this.formData.selectedFile = event.target.files[0];
      let nextSibling = event.target.nextElementSibling;
      nextSibling.innerText = event.target.files[0].name;
    },
    save: function () {
      this.loading = true;
      this.formError = null;
      const fd = new FormData();
      console.log(this.formData);
      if (this.formData.id) {
        fd.append('id', this.formData.id);
      }
      if (this.formData.name) {
        fd.append('name', this.formData.name);
      }
      if (this.formData.data) {
        fd.append('data', this.formData.data);
      }
      if (this.formData.selectedFile) {
        fd.append('image', this.formData.selectedFile);
      }
      axios.post(this.uploadUri, fd, {
        onUploadProgress: uploadEvent => {
          console.log(Math.round(uploadEvent.loaded / uploadEvent.total * 100));
        }
      }).then(res => {
        console.log(res.data);
        this.currentId = res.data.id;
        this.foodSurvey = res.data;
        this.formData.id = res.data.id;
        this.tab = 1;
        this.loading = false;
      }).catch(err => {
        console.log("err");
        console.log(err);
        this.formError = err.response.data.detail;
        this.loading = false;
      });
    },
    saveShape: function (shape) {
      if (this.saveTimeout) {
        clearTimeout(this.saveTimeout);
      }
      this.saveTimeout = setTimeout(() => {
        this.disableKonva = true;
        console.log(shape);
        const fd = new FormData();
        if (shape.id()) {
          fd.append('id', shape.id());
        }
        fd.append('foodSurveyId', this.currentId);
        fd.append('data', shape.toJSON());
        axios.post(this.saveSpotUri, fd).then(res => {
          console.log(res.data);
          if (!shape.id()) { // new Shape -> set Id
            shape.id(res.data.spots[res.data.spots.length - 1].id.toString());
          }
          this.foodSurvey = res.data;
          if (this.transformer.nodes().length > 0 &&
              this.transformer.nodes()[0].id() === shape.id()) {
            this.selectedSpotId = shape.id();
          }
          this.disableKonva = false;
        }).catch(err => {
          console.error(err);
        });
      }, 200);
    },
    saveSpot: function (spot) {
      if (this.saveTimeout) {
        clearTimeout(this.saveTimeout);
      }
      this.saveTimeout = setTimeout(() => {
        this.disableKonva = true;
        console.log(spot);
        const fd = new FormData();
        fd.append('id', spot.id);
        fd.append('foodSurveyId', this.currentId);
        fd.append('name', spot.name);
        axios.post(this.saveSpotUri, fd).then(res => {
          this.foodSurvey = res.data;
          this.disableKonva = false;
        }).catch(err => {
          console.error(err);
        });
      }, 200);
    },
    spotUp(spot) {
      this.disableKonva = true;
      const fd = new FormData();
      fd.append('id', spot.id);
      fd.append('foodSurveyId', this.currentId);
      fd.append('action', "up");
      axios.post(this.saveSpotUri, fd).then(res => {
        this.foodSurvey = res.data;
        this.disableKonva = false;
      }).catch(err => {
        console.error(err);
      });
    },
    spotDown(spot) {
      this.disableKonva = true;
      const fd = new FormData();
      fd.append('id', spot.id);
      fd.append('foodSurveyId', this.currentId);
      fd.append('action', "down");
      axios.post(this.saveSpotUri, fd).then(res => {
        this.foodSurvey = res.data;
        this.foodSurvey.spots = res.data.spots;
        this.disableKonva = false;
      }).catch(err => {
        console.error(err);
      });
    },
    removeSpot: function (spotId) {
      this.disableKonva = true;
      const fd = new FormData();
      fd.append('id', spotId);
      axios.post(this.removeSpotUri, fd).then(res => {
        this.$set(this, 'foodSurvey', res.data);
        let spotShape = this.stage.findOne('#' + spotId);
        if (spotShape) {
          spotShape.destroy();
          if (this.selectedSpotId === spotId.toString()) {
            this.transformer.nodes([]);
            this.selectedSpotId = null;
          }
        }
        this.disableKonva = false;
      }).catch(err => {
        console.error(err);
      });
    },
    initDrawing() {
      let self = this;
      this.activatePainting = false;
      this.$refs.image.src = '/food-survey/image/' + this.currentId + '?t=' + new Date().getTime();
      console.log('init drawing');
      if (this.$refs.image.naturalWidth !== 0) {
        self.imageWidth = this.$refs.image.naturalWidth;
        self.imageHeight = this.$refs.image.naturalHeight;
        console.log("set image size!");
        this.createStage();
      }
      this.$refs.image.onload = () => {
        self.imageWidth = this.$refs.image.naturalWidth;
        self.imageHeight = this.$refs.image.naturalHeight;
        console.log("image loaded!");
        self.createStage();
      };
    },
    createStage() {
      console.log('create stage');
      let self = this;
      let mode = 'brush';
      let lastLine;
      let layer;

      if (this.stage) {
        this.stage.destroy();
      }
      this.stage = new Konva.Stage({
        container: 'container',
        width: this.imageWidth,
        height: this.imageHeight,
        x: 0,
        y: 0,
        scaleX: 1,
        scaleY: 1
      });
      layer = new Konva.Layer();
      this.stage.add(layer);

      this.transformer = new Konva.Transformer({
        shouldOverdrawWholeArea: true,
        ignoreStroke: true,
        padding: 10,
        anchorSize: 20,
        flipEnabled: false,
        enabledAnchors: ['top-left', 'top-right', 'bottom-left', 'bottom-right']
      });
      layer.add(self.transformer);
      this.foodSurvey.spots.forEach((spot) => {
        if (spot.data) {
          let spotLine = Konva.Stage.create(spot.data, 'container');
          spotLine.id(spot.id.toString());
          layer.add(spotLine);
          self.addEvents(spotLine);
        }
      });
      this.stage.on('mousedown touchstart', function (e) {
        if (self.activatePainting) {
          self.isPainting = true;
          let pos = self.getScaledPointerPosition();
          lastLine = new Konva.Line({
            name: 'line',
            closed: true,
            stroke: '#df4b26',
            draggable: true,
            strokeWidth: self.strokeWidth,
            globalCompositeOperation:
                mode === 'brush' ? 'source-over' : 'destination-out',
            // round cap for smoother lines
            lineCap: 'round',
            lineJoin: 'round',
            // add point twice, so we have some drawings even on a simple click
            points: [pos.x, pos.y, pos.x, pos.y],
          });
          self.addEvents(lastLine);
          layer.add(lastLine);
        }
      });

      this.stage.on('mouseup touchend', function () {
        if (self.activatePainting) {
          self.activatePainting = false;
          self.isPainting = false;
          self.transformer.nodes([lastLine]);
          self.saveShape(lastLine);
        }
      });

      // and core function - drawing
      this.stage.on('mousemove touchmove', function (e) {
        if (!self.isPainting || !self.activatePainting) {
          self.isPainting = false;
          return;
        }
        // prevent scrolling on touch devices
        e.evt.preventDefault();

        let pos = self.getScaledPointerPosition();
        let newPoints = lastLine.points().concat([pos.x, pos.y]);
        lastLine.points(newPoints);
      });

      this.stage.on('click tap', function (e) {
        // if click on empty area - remove all selections
        if (e.target === self.stage) {
          self.transformer.nodes([]);
          self.selectedSpotId = null;
          return;
        }

        // do nothing if clicked NOT on our rectangles
        if (!e.target.hasName('line')) {
          return;
        }
        self.transformer.moveToTop();
        const isSelected = self.transformer.nodes().indexOf(e.target) >= 0;
        self.selectedSpotId = e.target.id();
        if (!isSelected) {
          self.transformer.nodes([e.target]);
          self.transformer.shouldOverdrawWholeArea(true);
          layer.draw();
        }
      });

      this.fitStageIntoParentContainer();
      // adapt the stage on any window resize
      window.addEventListener('resize', self.fitStageIntoParentContainer);

    },
    addEvents: function (line) {
      let self = this;
      line.on('transform', (event) => {
        // if scale: resize line width
        let newStrokeWidth;
        if (event.target.scaleX() < 1) {
          newStrokeWidth = (self.strokeWidth * (1 - event.target.scaleX() + 1));
        } else {
          newStrokeWidth = self.strokeWidth / event.target.scaleX();
        }
        event.target.strokeWidth(newStrokeWidth);
        self.saveShape(event.target);
      });
      line.on('dragend', (event) => {
        self.saveShape(event.target);
      });
    },
    selectSpot(spot) {
      let spotShape = this.stage.findOne('#' + spot.id);
      if (spotShape) {
        this.selectedSpotId = spot.id.toString();
        this.transformer.nodes([spotShape]);
      }
    },
    deleteShape() {
      console.log(this.transformer.nodes());
      if (this.transformer.nodes().length > 0) {
        this.transformer.nodes()[0].destroy();
        this.transformer.nodes([]);
      }
    },
    getScaledPointerPosition() {
      let pointerPosition = this.stage.getPointerPosition();
      console.log(pointerPosition);
      let stageAttrs = this.stage.attrs;
      console.log(stageAttrs.x);

      let x = (pointerPosition.x - stageAttrs.x) / stageAttrs.scaleX;
      let y = (pointerPosition.y - stageAttrs.y) / stageAttrs.scaleY;
      return {x: x, y: y};
    },
    fitStageIntoParentContainer() {
      let container = document.querySelector('#stage-parent');
      let scale;
      if (this.imageWidth < container.offsetWidth) {
        scale = 1;
      } else {
        scale = container.offsetWidth / this.imageWidth;
      }
      console.log(scale);
      this.stage.width(this.imageWidth * scale);
      this.stage.height(this.imageHeight * scale);
      this.stage.scale({x: scale, y: scale});
    },
    downloadImage() {
      this.transformer.nodes([]);
      // let dataURL = this.stage.toDataURL({pixelRatio: 1});
      let dataURL = '/food-survey/image/' + this.currentId + '/1';
      let link = document.createElement('a');
      link.download = 'stage.png';
      link.href = dataURL;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    },
    home() {
      window.location.href = this.homeUri;
    }
  }
}
</script>

<style scoped>
.error {
  color: #990000;
}

#stage-parent {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  overflow: hidden;
}

#container {
}

.disable-konva {
  display: flex;
  align-items: center;
  justify-content: center;
  position: absolute;
  left: 0;
  top: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.25);
  z-index: 100;
}
</style>
