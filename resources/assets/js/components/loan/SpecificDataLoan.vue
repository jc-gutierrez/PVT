<template>
  <v-container fluid>
    <ValidationObserver ref="observer">
      <v-form>
        <!--v-card-->
        <v-tooltip top>
          <template v-slot:activator="{ on }">
            <v-btn
              fab
              dark
              x-small
              :color="'error'"
              top
              right
              absolute
              v-on="on"
              style="margin-right: 45px;"
              @click.stop="resetForm()"
              v-show="editable"
            >
              <v-icon>mdi-close</v-icon>
            </v-btn>
          </template>
          <div>
            <span>Cancelar</span>
          </div>
        </v-tooltip>

        <v-tooltip top v-if="$store.getters.userRoles.includes('PRE-tesoreria')">
          <template v-slot:activator="{ on }">
            <v-btn
              fab
              dark
              x-small
              :color="editable ? 'danger' : 'success'"
              top
              right
              absolute
              v-on="on"
              style="margin-right: -9px;"
              @click.stop="editLoan()"
            >
              <v-icon v-if="editable">mdi-check</v-icon>
              <v-icon v-else>mdi-pencil</v-icon>
            </v-btn>
          </template>
          <div>
            <span v-if="editable">Guardar</span>
            <span v-else>Editar</span>
        </div>
        </v-tooltip>
        <v-row justify="center">
            <v-col cols="12">
              <v-container class="py-0">
                <v-row>
                  <slot name="title"></slot>
                  <br />
                  <v-col cols="12" md="4">
                    <v-layout row wrap>
                      <v-flex xs12 class="px-2">
                        <fieldset class="pa-3">
                          <v-toolbar-title>PRÉSTAMO</v-toolbar-title>
                          <br>
                          <p>PLAZO EN MESES: {{loan.loan_term}}</p>
                          <p>MONTO SOLICITADO: {{loan.amount_approved}}</p>
                          <p>PROMEDIO LIQUIDO PAGABLE: {{loan.payable_liquid_calculated}}</p>
                          <p>TOTAL BONOS: {{loan.bonus_calculated}}</p>
                          <p>LIQUIDO PARA CALIFICACION: {{loan.liquid_qualification_calculated}}</p>
                          <p>CALCULO DE CUOTA: {{loan.estimated_quota}}</p>
                          <p>INDICE DE ENDEUDAMIENTO: {{loan.indebtedness_calculated}}</p>
                          <p>MONTO MAXIMO SUGERIDO : {{loan.amount_approved}}</p>
                        </fieldset>
                      </v-flex>
                    </v-layout>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-card-text class="py-0">
                      <v-layout row wrap>
                        <v-flex xs12 class="px-2">
                          <fieldset class="pa-3">
                            <v-toolbar-title>GARANTE</v-toolbar-title>
                            <ul style="list-style: none" class="pa-0">
                              <li v-for="(guarantor,i) in loan.guarantors" :key="i" >
                                <br>
                                <p>CÉDULA DE IDENTIDAD: {{guarantor.identity_card +" "+ identityCardExt(guarantor.city_identity_card_id) }}</p> 
                                                         
                                <p>NOMBRE COMPLETO: {{$options.filters.fullName(guarantor, true)}}</p>
                                <p>PORCENTAJE DE PAGO: {{guarantor.pivot.payment_percentage}} %</p>

                                </li>
                              </ul>
                              <br>
                               <p v-if="loan.guarantors.length==0">NO TIENE GARANTES</p>
                          </fieldset>
                        </v-flex>
                      </v-layout>
                    </v-card-text>
                  </v-col>
                  <v-col cols="12" md="4" >
                    <v-card-text class="py-0">
                      <v-layout row wrap>
                        <v-flex xs12 class="px-2">
                          <fieldset class="pa-3">
                            <v-toolbar-title>DESEMBOLSO</v-toolbar-title>
                            <br>
                            <v-menu
                              v-model="dates.disbursementDate.show"
                              :close-on-content-click="false"
                              transition="scale-transition"
                              offset-y
                              max-width="290px"
                              min-width="290px"
                              :disabled="!editable"

                            >
                            <template v-slot:activator="{ on }">
                              <v-text-field
                                dense
                                :outlined="editable"
                                :readonly="!editable"
                                v-model="dates.disbursementDate.formatted"
                                label="FECHA DE DESEMBOLSO"
                                hint="Día/Mes/Año"
                                persistent-hint
                                append-icon="mdi-calendar"
                                v-on="on"

                              ></v-text-field>
                            </template>
                            <v-date-picker v-model="loan.disbursement_date" no-title @input="dates.disbursementDate.show = false"></v-date-picker>
                            </v-menu><br>
                            <v-select
                              dense
                              :outlined="editable"
                              :readonly="!editable"
                              :items="payment_types"
                              item-text="name"
                              item-value="id"
                              label="TIPO"
                              v-model="loan.payment_type_id"
                             ></v-select>
                            <v-text-field
                              :outlined="editable"
                              :readonly="!editable"
                              :label="loan.payment_type_id=='1'? 'NRO DE CUENTA':loan.payment_type_id=='2'? 'NRO DE CHEQUE':loan.payment_type_id=='3'?'NRO DE RECIBO':'OTRO'"
                              v-model="loan.number_payment_type"
                            ></v-text-field>
                          </fieldset>
                        </v-flex>
                      </v-layout>
                    </v-card-text>
                  </v-col>
                </v-row>
              </v-container>
            </v-col>
          </v-row>
        <!--/v-card-->
      </v-form>
    </ValidationObserver>
  </v-container>
</template>
<script>
export default {
  name: "specific-data-loan",
  props: {
    loan: {
      type: Object,
      required: true
    }
  },
   data: () => ({
    editable: false,
    reload: false,
    payment_types:[],
    city: [],
    dates: {
    disbursementDate:{
          formatted: null,
          picker: false
        }
      },
  }),
  beforeMount(){
    this.getPaymentTypes()
    this.getCity()
  },
  mounted() {
    this.formatDate('disbursementDate', this.loan.disbursement_date)
  },
  watch: {
    'loan.disbursement_date': function(date) {
      this.formatDate('disbursementDate', date)
    }
  },
  methods:{
    formatDate(key, date) {
      if (date) {
        this.dates[key].formatted = this.$moment(date).format('L')
      } else {
        this.dates[key].formatted = null
      }
    },
    async getCity() {
      try {
        this.loading = true
        let res = await axios.get(`city`)
        this.city = res.data
        console.log("ciudad "+ this.city)

      } catch (e) {
        console.log(e)
      } finally {
        this.loading = false
      }
    },
    identityCardExt(id){
      let ext
      if(id != null){
      ext = this.city.find(o => o.id == id).first_shortened
      console.log( ext)
      return ext
      }else{
        return ''
      }
    },
    resetForm() {
      this.editable = false
      this.reload = true
      this.$nextTick(() => {
      this.reload = false
      })
    },
    async editLoan(){
      try {
        if (!this.editable) {
          this.editable = true
          console.log('entro al grabar por verdadero :)')
        } else {
          console.log('entro al grabar por falso :)')
          //Edit desembolso
          if((this.loan.disbursement_date != '' && this.loan.number_payment_type != '') && (this.loan.disbursement_date != null && this.loan.number_payment_type != null)){
            let res = await axios.patch(`loan/${this.loan.id}`, {
            disbursement_date:this.loan.disbursement_date,
            payment_type_id: this.loan.payment_type_id,
            number_payment_type: this.loan.number_payment_type,
            validated: this.loan.number_payment_type
          })
            this.toastr.success('Se registró correctamente.')
            this.editable = false
          }else{
            this.toastr.error('Faltan registar campos en Desembolso. Registre la fecha, tipo y nro de documento.');
            
          }

        }
      } catch (e) {
        console.log(e)
      } finally {
        this.loading = false
      }
    },
     async getPaymentTypes() {
      try {
        this.loading = true
        let res = await axios.get(`payment_type`)
        this.payment_types = res.data
        console.log(this.payment_types+'este es el tipo de desembolso');
      } catch (e) {
        console.log(e)
      } finally {
        this.loading = false
      }
    },
  }
}
</script>