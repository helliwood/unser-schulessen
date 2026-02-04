<template>
    <div>
        <div class="text-center" :hidden="loaded">
            <b-spinner label="Spinning" variant="primary"></b-spinner>
        </div>
        <div :hidden="!loaded">
            <b-table
                    v-bind="$attrs"
                    v-on="$listeners"
                    ref="table"
                    id="my-table"
                    striped
                    :busy.sync="isBusy"
                    :items="myProvider"
                    :current-page="currentPage"
                    :api-url="url"
                    :fields="fields"
                    :per-page="perPage"
                    :sort-by="sortBy"
                    :sort-desc="sortDesc"
                    :hover="true"
                    :show-empty="true"
                    :caption="caption"
                    @row-clicked="rowClickedHere"
                    empty-text="Keine Daten vorhanden">
                <template v-for="(_, slot) of $scopedSlots" v-slot:[slot]="scope">
                    <slot :name="slot" v-bind="scope" :row="scope" :callAndRefresh="callAndRefresh"
                          :setApiUrlAndRefresh="setApiUrlAndRefresh"/>
                </template>
            </b-table>
            <b-row>
                <b-col class="d-flex align-items-center">
                    <b-pagination
                            align="center"
                            v-model="currentPage"
                            :total-rows="totalRows"
                            :per-page="perPage"
                            class="my-0"></b-pagination>
                    <div class="ml-auto">{{legend()}}</div>
                </b-col>
            </b-row>
        </div>
    </div>
</template>

<script>
    import axios from 'axios'
    import qs from 'qs';

    axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

    export default {
        name: "data-table",
        props: {
            fields: Array | String,
            apiUrl: String,
            directory:{
              tree: [],
            },
            page: {
                type: Number,
                default: 1
            },
            perPage: {
                type: Number,
                default: 10
            },
            sortBy: String,
            sortDesc: {
                type: Boolean,
                default: false
            },
            rowClicked: Function,
            caption: String
        },
        data() {
            return {
                url: this.apiUrl,
                loaded: false,
                currentPage: this.page,
                totalRows: 0,
                isBusy: false
            }
        },
        methods: {
            legend() {
                var from = (this.currentPage - 1) * this.perPage;
                var to = (this.currentPage - 1) * this.perPage + this.perPage;
                return 'Zeige ' + (from <= 0 ? 1 : from) + ' bis ' + (to > this.totalRows ? this.totalRows : to) + ' von ' + this.totalRows;
            },
            myProvider(ctx) {
                // Here we don't set isBusy prop, so busy state will be
                // handled by table itself
                this.isBusy = true;
                const params = '?page=' + ctx.currentPage + '&size=' + ctx.perPage + '&sort=' + ctx.sortBy + '&sortDesc=' + ctx.sortDesc;
                let promise = axios.get(ctx.apiUrl ? ctx.apiUrl + params : '' + params);
                return promise.then((data) => {
                    // Here we could override the busy state, setting isBusy to false
                    this.loaded = true;
                    this.isBusy = false;
                    this.totalRows = data.data.totalRows;
                    return data.data.items;
                }).catch(error => {
                    // Here we could override the busy state, setting isBusy to false
                    this.isBusy = false;
                    // Returning an empty array, allows table to correctly handle
                    // internal busy state in case of error
                    return [];
                });
            },
            callAndRefresh(data, url = null) {
                var self = this;
                this.isBusy = true;
                let promise = axios.post(url ? url : (this.apiUrl ? this.apiUrl : ''), qs.stringify(data), {
                    headers: {
                        'Content-Type':
                            'application/x-www-form-urlencoded'
                    }
                });
                promise.then((data) => {
                    self.isBusy = false;
                    self.$nextTick(function () {
                        self.$refs.table.refresh();
                    });
                }).catch(error => {
                    console.log("CATCH");
                    self.isBusy = false;
                    self.$refs.table.refresh();
                });
            },
            call () {
              alert('sfdg');
            },
            setApiUrlAndRefresh(apiUrl) {
                this.url = apiUrl;
                this.$nextTick(function () {
                    this.$refs.table.refresh();
                });
            },
            rowClickedHere(item, index) {
                if (this.rowClicked) {
                    this.rowClicked(item, index);
                }
            }
        }
    }
</script>

<style scoped>

</style>
