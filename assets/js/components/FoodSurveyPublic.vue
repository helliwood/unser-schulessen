<template>
  <div class="food-survey-public">
    <div class="head">
      <div class="logo"></div>
    </div>
    <div class="content">
      <div ref="layer" class="layer position-absolute" v-if="!running">
        <img src="../../img_foodsurvey/Icon_Startemoji.png" class="img-fluid" @click="start" role="button" width="50%"/>
      </div>
      <div class="label position-absolute" v-if="running && currentLabel">
        {{ currentLabel }}
      </div>
      <div id="stage-parent" ref="stageParent">
        <div id="container" ref="container"></div>
      </div>
      <img ref="image" :src="imgUri"/>
    </div>
    <div class="toolbar">
      <div class="buttons">
        <button @click="start" v-if="!running">
          <div class="py-2 px-4">Start</div>
        </button>
        <button @click="restart" v-if="end">
          <div class="py-2 px-4">Neustarten</div>
        </button>
        <button v-if="running && !end" @click="vote(1)" class="mr-3">
          <img src="../../img_foodsurvey/Icon_gut.png" class="p-2" width="80" height="80"/>
        </button>
        <button v-if="running && !end" @click="vote(0)" class="mr-3">
          <img src="../../img_foodsurvey/Icon_schlecht.png" class="p-2" width="80" height="80"/>
        </button>
        <button v-if="running && !end" @click="vote(-1)">
          <img src="../../img_foodsurvey/Icon_keine_Ahnungi.png" class="p-2" width="100" height="80"/>
        </button>
      </div>
    </div>
  </div>
</template>

<script>

import Konva from "konva";
import {emojisplosions} from "emojisplosion";
import axios from 'axios';
import qs from "qs";

export default {
  name: "food-survey-public",
  props: {
    foodSurvey: Object,
    imgUri: String,
    saveUri: String
  },
  data() {
    return {
      stage: null,
      layer: null,
      imageWidth: null,
      imageHeight: null,
      running: false,
      end: false,
      currentIndex: 1,
      currentLabel: null,
      explosion: null,
      data: {}
    }
  },
  mounted() {
    if (this.$refs.image.naturalWidth !== 0) {
      this.imageWidth = this.$refs.image.naturalWidth;
      this.imageHeight = this.$refs.image.naturalHeight;
      console.log("set image size!");
      this.createStage();
    }
    this.$refs.image.onload = () => {
      this.imageWidth = this.$refs.image.naturalWidth;
      this.imageHeight = this.$refs.image.naturalHeight;
      console.log("image loaded!");
      this.createStage();
    };
  },
  methods: {
    start() {
      this.running = true;
      this.showSpot(this.currentIndex);
    },
    restart() {
      this.end = false;
      this.running = false;
      this.currentIndex = 1;
      this.data = {};
      if (this.explosion) {
        this.explosion.cancel();
      }
    },
    vote(value) {
      this.data[this.currentIndex] = value;
      if (this.currentIndex + 1 <= this.foodSurvey.spots.length) {
        this.currentIndex++;
        this.showSpot(this.currentIndex);
      } else {
        //Ende
        axios.post(this.saveUri, qs.stringify(this.data), {
          headers: {
            'Content-Type':
                'application/x-www-form-urlencoded'
          }
        }).then((result) => {
          console.log(result);
        });
        this.layer.destroyChildren();
        this.end = true;
        this.explosion = emojisplosions({
              physics: {
                fontSize: {
                  max: 70,
                  min: 30,
                },
              }
            }
        );
        console.log(this.data);
      }

    },
    createStage: function () {
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
      this.layer = new Konva.Layer();
      this.stage.add(this.layer);
      //this.showSpot(2);
      this.fitStageIntoParentContainer();
      // adapt the stage on any window resize
      window.addEventListener('resize', this.fitStageIntoParentContainer);
    },
    showSpot: function (index) {
      let spot = this.foodSurvey.spots[index - 1];
      console.log(spot);
      if(spot.name) {
        this.currentLabel = spot.name;
      }
      let spotLine = Konva.Stage.create(spot.data, 'container');
      spotLine.draggable(false);
      let spotLineBorder = spotLine.clone();
      spotLine.globalCompositeOperation("destination-out");
      spotLine.fill("black");
      this.layer.destroyChildren();
      let rect = new Konva.Rect({
        width: this.imageWidth,
        height: this.imageHeight,
        fill: "black",
        opacity: 0.6
      });
      this.layer.add(rect);
      this.layer.add(spotLine);
      this.layer.add(spotLineBorder);
      //let pos = this.getPosition(spotLine);
      //console.log(pos);
      /*
      const text = new Konva.Text({
        x: pos.x,
        y: pos.y,
        text: '😃️ 🙁 🤷🏼',
        fontSize: 100
      });*/
      /*
      const text = new Konva.Rect({
        x: pos.x,
        y: pos.y,
        fill: "red",
        width: 100,
        height: 100
      });
      this.layer.add(text);*/

      // this.$refs.layer.style.left = pos.x + "px";
      // this.$refs.layer.style.top = pos.y + "px";
    },
    getPosition: function (shape) {
      let minX, minY;
      shape.points().forEach((value, index) => {
        console.log(value, index);
        if (index % 2) {
          if (!minY || value < minY) {
            minY = value;
          }
        } else {
          if (!minX || value < minX) {
            minX = value;
          }
        }
      });
      return {x: minX + shape.x(), y: minY + shape.y()};
    },
    fitStageIntoParentContainer() {
      let container = document.querySelector('#stage-parent');
      let maxWidth = Math.min(container.offsetWidth, this.imageWidth);
      let maxHeight = Math.min(container.offsetHeight, this.imageHeight);

      let rnd = Math.min(maxWidth / this.imageWidth, maxHeight / this.imageHeight);
      let scale = rnd;
      let size = {w: Math.round(this.imageWidth * rnd), h: Math.round(this.imageHeight * rnd)};
      console.log(scale);

      this.stage.width(this.imageWidth * scale);
      this.stage.height(this.imageHeight * scale);
      this.$refs.image.width = this.stage.width();
      this.$refs.image.height = this.stage.height();
      this.stage.scale({x: scale, y: scale});
    },
  }
}
</script>

<style scoped>
.food-survey-public {
  position: absolute;
  overflow: hidden;
  top: 0px;
  left: 0px;
  right: 0px;
  bottom: 0px;
  display: flex;
  justify-content: center;
  align-items: center;
}

.food-survey-public .head {
  position: absolute;
  z-index: 1000;
  background-color: #e9e4de;
  height: 20px;
  top: 0;
  left: 0;
  right: 0;
}

.food-survey-public .head .logo {
  position: absolute;
  background-image: url("../../img/logo.svg");
  background-size: contain;
  width: 100px;
  height: 100px;
  top: 20px;
  left: 20px;
}

.food-survey-public .toolbar {
  position: absolute;
  z-index: 1000;
  background-color: #e9e4de;
  display: flex;
  justify-content: center;
  height: 90px;
  bottom: 0;
  left: 0;
  right: 0;
}

.food-survey-public .toolbar .buttons {
  position: absolute;
  background-color: #1e5625;
  border-radius: 30px;
  top: -40px;
  padding: 20px 30px;
}

.food-survey-public .toolbar .buttons button {
  background: #ef6903;
  border: none;
  color: #FFF;
  font-size: 2.5rem;
  font-weight: bolder;
  border-radius: 30px;
}

.food-survey-public .label {
  position: absolute;
  z-index: 1000;
  background-color: #97c01f;
  border-radius: 30px;
  color: #FFF;
  padding: 10px 20px;
  font-size: 1.5rem;
  font-weight: bold;
  top: 20px;
  right: 20px;
}

.food-survey-public .label:before {
  position: absolute;
  background-color: #ff6b00;
  border-radius: 20px;
  display: block;
  content: " ";
  width: 20px;
  height: 20px;
  top: 5px;
  left: -8px;
}

.food-survey-public .content {
  position: absolute;
  top: 20px;
  left: 0;
  right: 0;
  bottom: 90px;
  display: flex;
  justify-content: center;
  align-items: center;
}

#stage-parent {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  justify-content: center;
  align-items: center;
}

#stage-parent > * {
  margin: auto;
}

.layer {
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 100;
}
</style>
