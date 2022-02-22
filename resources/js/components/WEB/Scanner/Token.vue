<template>
  <section class="scanner-token-component">
    <div class="scan-general-info-component-container">
      <scan-general-info @selected="selected" showSCAN></scan-general-info>
    </div>
    <section v-if="token" class="token-scanner-report-section">
      <section class="scanner-token-info-container">
        <searched-token-card :token="token" :selectable="false" size="is-large" type="is-inline" :showReports="false" />
        <p class="scanner-token-address cur-p" @click.prevent="$root.clipboard(token.address, 'Address copied to clipboard!')">
          {{token.address}}
          <i class="far fa-copy cur-p m-l-7"></i>
        </p>
        <div class="token-info-prices">
          <template v-if="loading.info">
            <div class="token-info-price-container">
              <div class="is-placeholder-loading" style="width:200px;height:16px;margin:16px 0 5px;border-radius:999px;"></div>
            </div>
            <div class="token-info-price-container">
              <div class="is-placeholder-loading" style="width:150px;height:16px;margin:16px 0 5px;border-radius:999px;"></div>
            </div>
          </template>
          <template v-else>
            <div class="token-info-price-container">
              <div v-if="info" class="tipc-price">{{$root.formatPrice(info.prices.busd)}} USD</div>
              <div class="tipc-percent has-text-success">1.98%</div>
            </div>
            <div class="token-info-price-container">
              <div v-if="info" class="tipc-price">{{$root.formatPrice(info.prices.bnb)}} BNB</div>
              <div class="tipc-percent has-text-danger">8.98%</div>
            </div>
          </template>
          <!-- <div class="token-info-price-container">
            <div class="tipc-price">0.0005476 ETH</div>
            <div class="tipc-percent has-text-success">2.56%</div>
          </div> -->
        </div>

        <div class="scam-chance-container">
          <p>{{trans('scanner.chance')}}</p>
          <b-slider class="scam-slider" :value="token.scamlevel" type="is-transparent" size="is-small" :tooltip="false" rounded></b-slider>
        </div>

        <div v-if="token.address !== $root.scan.contract" class="token-actions">
          <a class="button is-primary m-t-10" @click.prevent="report(token)">{{trans('blacklist.go_to_report')}}</a>
          <a href="https://t.me/scandefigroup" target="_blank" class="button is-primary m-t-10">{{trans('scanner.quit_token')}}</a>
        </div>
      </section>
      
      <section v-if="token.address !== $root.scan.contract && reports && reports.length" class="scanner-token-reports-container">
        <article v-for="report in reverseReports" :key="report.id" class="scanner-token-report">
          <div class="scanner-token-report-head">
            <div v-if="report.meta.user.wallet" class="wallet-logo" style="width:27px;height:27px;overflow:hidden;"><img :src="`https://avatars.dicebear.com/api/identicon/${report.meta.user.wallet}.svg?colorLevel=300`" alt="Wallet avatar"></div>
            <div v-else class="wallet-logo" :style="{color: $root.$randomColor({ luminosity: 'light' })}">{{report.meta.user.wallet.slice(-2)}}</div>
            <div class="scanner-token-reporter">{{$root.shortenAddress(report.meta.user.wallet, 9)}}</div>
            <div class="scanner-token-report-date has-text-grey">{{$root.formatDate(report.created_at)}}</div>
          </div>
          <div class="scanner-token-report-complaints">
            <web-report-token-complaint  v-for="complaint in report.complaints" :key="complaint.id" :complaint="complaint" />
          </div>
          <div v-if="report.description" class="scanner-token-report-description">
            {{report.description}}
          </div>
          <div class="scanner-token-report-actions">
            <b-tooltip label="Comiing soon" type="is-black" size="is-small"><span><i class="fas fa-gavel"></i> {{trans('basic.controversy')}}</span></b-tooltip>
            <span @click="likeReport(report)"><i class="far fa-thumbs-up" :class="{'has-text-primary':report.liked_by_authuser}"></i></span>
            <span @click="dislikeReport(report)"><i class="far fa-thumbs-down" :class="{'has-text-danger':report.disliked_by_authuser}"></i></span>
          </div>
        </article>
      </section>
    </section>
  </section>
</template>

<script>
  export default {
    props: {},

    data() {
      return {
        token: null,
        reports: [],
        loading: {
          like: false,
          info:false,
          reports: false,
        },
        info: {
          data: {},
          prices: {
            busd: 0,
            bnb: 0,
          },
        },
        whitelist: [
          '0xe5b950d87eb917f9811188f2794e9aed0c1dcae7'.toLowerCase(),
        ],
      }
    },

    watch: {
      $route: {
        immediate: true,
        handler(val) {
          this.init();
        }
      },
    },

    computed: {
      reverseReports() {
        return this.reports.reverse();
      },
    },

    methods: {
      init() {
        this.address = this.$route.params.address;
        if(this.whitelist.includes(this.address.toLowerCase())){
          this.$router.push({ name: 'scanner' });
        }else{
          this.getScannerToken();
        }
      },
      getScannerToken(address = null) {
        this.loading.info = true;
        address = address ? address : this.$route.params.address;
        axios.get(`/api/tokens/${address}/scanner`).then(response => {
          this.token = response.data.token;
          this.getTokenPrice(address);
          this.getBscTokenInfo(address);
          this.getTokenReports(address);
        });
      },
      getTokenReports(address = null) {
        this.loading.reports = true;
        address = address ? address : this.$route.params.address;
        axios.get(`/api/tokens/${address}/reports`).then(response => {
          this.reports = response.data.reports;
          this.loading.reports = false;
        });
      },
      getTokenPrice(address){
        this.loading.info = true;
        this.$root.pancakeTokenInfo(address).then(response => {
          this.info.prices.busd = response.data.price;
          this.info.prices.bnb = response.data.price_BNB;
          this.loading.info = false;
        });
      },
      selected(data) {
        if(this.token && data.item.address === this.token.address) return;

        if(data.type === 'token'){
          if(this.whitelist.includes(this.address.toLowerCase())){
            this.$router.push({ name: 'scanner' });
          }else{
            this.getScannerToken(data.item.address);
            this.$router.push({ name: 'scanner.token', params: { address: data.item.address } });
          }
        }
        if(data.type === 'wallet'){
          this.$router.push({ name: 'scanner.wallet', params: { address: data.item.address } });
        }
      },
      report(token) {
        let simpletoken = _.clone(token);
        delete simpletoken.reports;
        delete simpletoken.comments;
        this.$root.reportToken(simpletoken);
      },
      async getBscTokenInfo(address = null) {
        address = address ? address : this.$route.params.address;
        await axios.get(`/api/bsc/tokens/${address}/info`).then(response => {
          this.info.data = response.data;
        });
      },
      async likeReport(report) {
        this.loading.like = true;
        await axios.post(`/reports/${report.id}/likes`).then(response => {
          this.getTokenReports();
        });
        this.loading.like = false;
      },
      async dislikeReport(report) {
        this.loading.like = true;
        await axios.post(`/reports/${report.id}/dislikes`).then(response => {
          this.getTokenReports();
        });
        this.loading.like = false;
      },
    },

    mounted() {
      this.init();
    }
  }
</script>
