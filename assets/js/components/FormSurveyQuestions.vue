<template>
  <div>
    <slot></slot>
    <!--        <div v-if="questionCount<60" class="d-flex">-->
    <b-dropdown-item @click="openPool" href="javascript:void(0);" class="">Frage(n) aus Pool
      hinzufügen
    </b-dropdown-item>
    <!--        </div>-->
    <b-modal id="modal-question-pool"
             title="Fragen-Pool"
             cancel-variant="primary-light"
             ok-only
             ok-title="schließen"
             v-on:show="showCategories">
      <data-table v-show="modalMode=='categories'"
                  api-url="/survey/proposal/categories"
                  sort-by="order"
                  :sort-desc="false"
                  :fields="[
                    {key: 'name', label: 'Name', sortable: false, thClass: 'border-top-0 pt-0'},
                    {key: 'actions', label: 'Optionen', sortable: false, class:'options', thClass: 'border-top-0 pt-0'}
                ]">
        <template v-slot:cell(actions)="{ row, callAndRefresh, setApiUrlAndRefresh }">
          <button class="btn btn-secondary" @click="showQuestions(row.item.id)">
            Öffnen
          </button>
        </template>
      </data-table>
      <data-table ref="questionsTable" v-show="modalMode=='questions'"
                  api-url="/survey/proposal/categories"
                  sort-by="order"
                  :sort-desc="false"
                  @row-clicked="rowClicked"
                  :fields="[
                    {key: 'select', label: '#', sortable: false, thClass: 'border-top-0 pt-0'},
                    {key: 'question', label: 'Frage', sortable: false, thClass: 'border-top-0 pt-0'}
                ]">
        <template v-slot:cell(select)="{ row, callAndRefresh, setApiUrlAndRefresh }">
          <input type="checkbox" :checked="selectedQuestions[row.item.id]"
                 @change="selectQuestion($event, row)"/>
        </template>
        <template v-slot:cell(question)="{ row, callAndRefresh, setApiUrlAndRefresh }">
          {{ row.item.question}} <i class="fa fa-leaf ml-1 " style="color: #006600;" v-if="row.item.sustainable"></i>
        </template>
      </data-table>
      <div class="text-center mt-2">
        <button v-show="modalMode=='questions'" class="btn btn-secondary" @click="showCategories()">Zurück
        </button>
        <button v-show="selectedQuestionsCount>0" class="btn btn-primary" @click="insertSelectedQuestions()">
          {{ selectedQuestionsCount }} Fragen einfügen
        </button>
      </div>
    </b-modal>
  </div>
</template>

<script>
import DataTable from "./DataTable";
import axios from 'axios';

export default {
  name: "form-survey-questions",
  props: {},
  components: {
    DataTable
  },
  data() {
    return {
      modalMode: 'categories',
      currentInput: null,
      questionCount: 0,
      selectedQuestions: {},
      selectedQuestionsCount: 0
    }
  },
  created() {
    this.$root.$on('openPool', () => {
      this.openPool();
    });
  },
  mounted() {
    this.$el.firstChild.childNodes.forEach((node, i) => {
      this.addButtons(node);
      this.questionCount++;
    });
  },
  methods: {
    showCategories() {
      this.modalMode = 'categories';
    },


    showQuestions(id) {
      this.$refs.questionsTable.setApiUrlAndRefresh('/survey/proposal/questions/' + id);
      this.modalMode = 'questions';
    },/*
            insertQuestion(question) {
                if (this.currentInput) {
                    this.currentInput.value = question;
                }
                this.$bvModal.hide('modal-question-pool');
            },*/
    add: function () {
      // var newIndex = this.getHighestIndex() + 1;
      // var template = this.createChild(this.$el.firstChild.dataset.prototype.replace(/__name__/g, newIndex));
      // this.addButtons(template);
      // this.$el.firstChild.appendChild(template);
      // this.questionCount++;
      console.log(this.selectedQuestions);
    },
    createChild(html) {
      var child = document.createElement('div');
      child.innerHTML = html;
      return child.firstChild;
    },
    addButtons(el) {
      var container = this.createChild('<div class="text-right"></div>');
      var delButton = this.createChild('<a href="javascript:void(0);" class="btn btn-danger"><i class="fas fa-trash-alt"></i></a>');
      //var poolButton = this.createChild('<a href="javascript:void(0);" class="btn btn-primary mr-2">Fragepool öffnen</a>');
      delButton.addEventListener('click', () => {
        el.remove();
        this.questionCount--;
      });
      /*poolButton.addEventListener('click', () => {
          this.$bvModal.show('modal-question-pool');
          this.currentInput = el.querySelector('input[id$="_question"]');
      });
      container.appendChild(poolButton);*/
      container.appendChild(delButton);
      el.appendChild(container);
      this.currentInput = el.querySelector('input[id$="_question"]')
    },
    openPool() {
      this.$bvModal.show('modal-question-pool');
      this.selectedQuestionsCount = 0;
      this.selectedQuestions = {};
    },
    getHighestIndex() {
      var highestIndex = 0;
      this.$el.firstChild.childNodes.forEach((node, i) => {
        var found = parseInt(node.id.toString().match(/\d+/)[0]);
        if (found > highestIndex) {
          highestIndex = found;
        }
      });
      return highestIndex;
    },
    selectQuestion(event, row) {
      if (event.target.checked) {
        this.selectedQuestions[row.item.id] = row.item;
      } else {
        delete this.selectedQuestions[row.item.id];
      }
      this.selectedQuestionsCount = Object.keys(this.selectedQuestions).length;
    },
    insertSelectedQuestions() {
      let url = location.href.split('/')
      let surveyId = url[url.length - 1];

      axios.post('/survey/ajax', {
        surveyId: surveyId,
        selectedQuestions: Object.keys(this.selectedQuestions)
      });
      location.reload();
      this.$bvModal.hide('modal-question-pool');
    },
    rowClicked(item, index) {
      if (!this.selectedQuestions[item.id]) {
        this.selectedQuestions[item.id] = item;
      } else {
        delete this.selectedQuestions[item.id];
      }
      this.selectedQuestionsCount = Object.keys(this.selectedQuestions).length;
      this.$forceUpdate();
    }
  }
}
</script>

<style scoped>

</style>
