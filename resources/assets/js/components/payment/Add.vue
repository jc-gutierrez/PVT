<template>
  <v-card flat>
    <v-card-title >
      <v-toolbar  dense color="tertiary" style="z-index: 1;">
        <v-toolbar-title>
          <Breadcrumbs/>
        </v-toolbar-title>
        <v-spacer></v-spacer>
      </v-toolbar>
    </v-card-title>
    <template>
      <v-container>
        <div>
          <v-row>
            <v-col  cols="4">
              {{"TITULAR: "+this.loan.lenders[0].last_name+" "+this.loan.lenders[0].mothers_last_name+" "+this.loan.lenders[0].first_name+" "+this.loan.lenders[0].second_name}}
            </v-col>
            <v-col  cols="3">
              {{"PRESTAMO: "+this.loan.code}}
            </v-col>
            <v-col  cols="2">
              {{'MONTO:'+this.loan.amount_approved}}
            </v-col>
             <v-col  cols="2">
              {{'CUOTA:'+this.loan.estimated_quota}}
            </v-col>
          </v-row>
          <Steps/>
        </div>
      </v-container>
    </template>
  </v-card>
</template>

<script>
import Steps from '@/components/payment/Steps'
import Breadcrumbs from '@/components/shared/Breadcrumbs'

export default {
  name: "loan-add",
  components: {
    Steps,
    Breadcrumbs
  },
  data: () => ({
    loan:{},
    degree_name: null,
    category_name: null
  }),
  computed: {
    isNew() {
      return this.$route.params.hash == 'new'
    }
  },
  beforeMount() {
    this.$store.commit('setBreadcrumbs', [
      {
        text: 'Nuevo Cobro',
        to: { name: 'flowIndex' }
      }
    ])
  },
  mounted() {
    this.getLoan(this.$route.query.loan_id);
  },
  methods:{
     async getLoan(id) {
      try {
        this.loading = true;
        let res = await axios.get(`loan/${id}`);
        this.loan = res.data;
        console.log('esta sacando el loan')
      } catch (e) {
        console.log(e);
      } finally {
        this.loading = false;
      }
    },
  setBreadcrumbs() {
    let breadcrumbs = [
      {
        text: 'Cobros',
        to: { name: 'flowIndex' }
      }
    ]
    if (this.isNew) {
      breadcrumbs.push({
        text: 'Nuevo Cobro',
        to: { name: 'flowIndex', params: { id: 'new' } }
      })
      } else {
      breadcrumbs.push({
        text: this.$options.filters.fullName(this.affiliate, true),
        to: { name: 'flowIndex', params: { id: this.affiliate.id } }
      })
    }
    this.$store.commit('setBreadcrumbs', breadcrumbs)
  }
  }
}
</script>