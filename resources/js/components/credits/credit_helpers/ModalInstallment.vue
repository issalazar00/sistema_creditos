<template>
  <div
    class="modal fade"
    id="cuotasModal"
    tabindex="-1"
    role="dialog"
    aria-labelledby="cuotasModalLabel"
    aria-hidden="true"
  >
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="cuotasModalLabel">Listado de Cuotas</h5>
          <button
            type="button"
            class="close"
            data-dismiss="modal"
            aria-label="Close"
          >
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div
            class="page-header d-flex justify-content-between p-4 border my-2"
          >
            <div class="form-row w-100">
              <div class="form-group col-2">
                <label for="amount">Monto a pagar:  <b>{{  amount_value | currency }}</b></label>
              </div>
              <div class="form-group col-4">
                <input
                  type="number"
                  step="any"
                  class="form-control"
                  id="amount"
                  placeholder="$"
                  v-model="amount_value"
                  :min="min_amount"
                />
                <small id="addAmountHelpBlock" class="form-text text-muted">
                  Monto mínimo
                  {{ min_amount | currency }}
                </small>
              </div>
              <div class="form-group col-3">
                <button
                  class="btn btn-primary my-auto"
                  @click="payCredit()"
                  v-if="amount_value > min_amount"
                >
                   Abonar  <b>{{  amount_value | currency }}</b>
                </button>
                <button v-else class="btn btn-secondary my-auto" disabled>
                  <i class="bi bi-currency-dollar"></i> Abonar a crédito
                </button>
              </div>
              <div class="form-group col-12 text-center">
                <button
                  class="btn btn-danger w-50 font-weight-bold"
                  @click="printTable()"
                >
                  <i class="bi bi-file-pdf"></i> Tabla de amortización
                </button>
              </div>
            </div>
          </div>
          <installment ref="Installment" />
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">
            Cerrar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import Installment from "./Installment.vue";
export default {
  components: { Installment },
  data() {
    return {
      id_credit: 0,
      allow_payment: 0,
      amount_value: 0,
      allow_payment: 1,
      min_amount: 0,
    };
  },
  methods: {
    listCreditInstallments: function (credit_id, allow_payment, credit) {
      this.id_credit = credit_id;
      this.allow_payment = allow_payment;
      this.min_amount = credit.installment_value;
      this.$refs.Installment.listCreditInstallments(credit_id, allow_payment);
    },

    payCredit() {
      var data = {
        amount: this.amount_value,
      };
      if (this.amount_value > 0) {
        axios
          .post(
            `api/installment/${this.id_credit}/pay-installment`,
            data,
            this.$root.config
          )
          .then((response) => {
            this.printEntryPdf(response.data.entry_id);
          })
          .finally(
            this.$refs.Installment.listCreditInstallments(
              this.id_credit,
              this.allow_payment
            )
          );
      } else {
        Swal.fire({
          icon: "error",
          title: "Oops...",
          text: `El valor debe ser mayor a ${this.min_amount}`,
        });
      }
    },

    printTable() {
      axios
        .get(
          `api/credits/amortization-table?credit_id=${this.id_credit}`,
          this.$root.config
        )
        .then((response) => {
          const pdf = response.data.pdf;
          var a = document.createElement("a");
          a.href = "data:application/pdf;base64," + pdf;
          a.download = `credit_${this.id_credit}-${Date.now()}.pdf`;
          a.target = "_blank"
          a.click();
        });
    },
    printEntryPdf: async function (entry_id) {
      try {
        const resp = await axios
        .get(`api/entries/show-entry/${entry_id}`, this.$root.config)
        .then((response) => {
          const pdf = response.data.pdf;
          var a = document.createElement("a");
          a.href = "data:application/pdf;base64," + pdf;
          a.download = `entrada_${entry_id}-${Date.now()}.pdf`;
          a.target = "_blank";
          a.click();
        });
      } catch (error) {
        
      }
    },
  },
};
</script>
