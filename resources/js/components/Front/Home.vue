<template>
 <div>
    <disbursements  v-if="$root.validateAuth" />
    <div class="page">
    <div class="page-header text-center">
      <h2>Simulador de Crédito</h2>
      <p>Puedes hacer una simulacion de tu crédito</p>
    </div>
    <div class="page-content">
      <div class="form-row">
        <div class="form-group col-md-4">
          <label for="monto">Monto</label>
          <input
            required
            type="number"
            class="form-control"
            id="monto"
            v-model="capital"
            step="any"
            placeholder="Ingresar monto"
          />
        </div>
        <div class="form-group col-md-4">
          <label for="tiempo">Tiempo en Meses</label>
          <input
            required
            type="number"
            class="form-control"
            id="tiempo"
            v-model="number_installments"
            placeholder="Ingresar cantidad de meses"
          />
        </div>
        <div class="form-group col-md-4">
          <label for="interest">Interés Mensual</label>
          <input
            required
            type="number"
            class="form-control"
            id="interest"
            v-model="interest"
            step="any"
            placeholder="Ingresar tasa de interés mensual"
          />
        </div>
        <div class="form-group col-md-4">
          <label for="additional_interest">Interés cobro y administración</label>
          <input
            required
            type="number"
            class="form-control"
            id="additional_interest"
            v-model="additional_interest"
            step="any"
            placeholder="Ingresar tasa de interés cobro y administración"
          />
        </div>
      </div>

      <simulator
        :capital="capital"
        :interest="interest"
        :number_installments="number_installments"
        :additional_interest="additional_interest"
        ref="Simulator"
      ></simulator>
    </div>
  </div>
 </div>
</template>

<script>
import Simulator from "../credits/credit_helpers/Simulator.vue";
import Disbursements from "../credits/outstanding_credits/Disbursements.vue";

export default {
  components: { Simulator, Disbursements },
  data() {
    return {
      interest: 3,
      capital: 500000,
      number_installments: 12,
      additional_interest:0
    };
  },
  methods: {
    simularCredit: function () {
      this.$refs.Simulator.openSimulator();
    },
  },
  mounted() {
    console.log("Component mounted.");
  },
};
</script>
