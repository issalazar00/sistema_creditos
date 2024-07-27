<template>
  <section class="tablex-responsive">
    <table class="table table-sm table-bordered table-responsive" style="overflow:visible">
      <thead>
        <tr class="text-center">
          <th>Fecha de vencimiento</th>
          <th>Nro. Cuota</th>
          <th>Valor</th>
          <th>Valor Capital</th>
          <th>Valor Interés</th>
          <th>Interés Cobro & Admin</th>
          <th>Saldo capital</th>
          <template v-if="allow_payment">
            <th>Mora</th>
            <th>Dias de mora</th>
            <th>Valor abonado</th>
            <th>Capital abonado</th>
            <th>Estado</th>
            <th>Pago a cuota</th>
            <th>Abono a cuota</th>
            <th v-if="$root.validatePermission('installment-reverse')">
              Reversar <br />
              Pago
            </th>
            <th style="min-width: 200px;">Compromiso <br> de pago</th>
            <th>Actualizar <br> estado</th>
          </template>

        </tr>
      </thead>
      <tbody>
        <tr v-for="quote in listInstallments" :key="quote.id">
          <th>{{ quote.payment_date }}</th>
          <td>{{ quote.installment_number }}
            <br>
            <small>ID: {{ quote.id }}</small>
          </td>
          <th class="text-right font-weight-bold">
            <span class="text-danger">{{
            quote.value_pending | currency
          }}</span>
            <br />
            <span class="text-dark small">{{ quote.value | currency }}</span>
          </th>
          <td class="text-right">
            <span class="text-danger">{{
            quote.capital_value_pending | currency
          }}</span>
            <br />
            <span class="text-dark small">{{
              quote.capital_value | currency
            }}</span>
          </td>
          <td class="text-right">
            <span class="text-danger">{{
              quote.interest_value_pending | currency
            }}</span>
            <br />
            <span class="text-dark small">{{
              quote.interest_value | currency
            }}</span>
          </td>
          <td class="text-right">
            <span class="text-danger">{{
              quote.additional_interest_value_pending | currency
            }}</span>
            <br />
            <span class="text-dark small">{{
              quote.additional_interest_value | currency
            }}</span>
          </td>
          <td class="text-right">
            {{ quote.capital_balance | currency }}
          </td>
          <template v-if="allow_payment">
            <td class="text-right">
              <span class="text-danger">{{ quote.late_interests_value_pending | currency }}<br /></span>
              <span class="text-dark small" v-if="quote.days_past_due">{{
            quote.late_interests_value | currency
          }}</span>
            </td>
            <td class="text-danger">
              {{ quote.days_past_due }}
            </td>
            <td class="text-right">
              {{ quote.paid_balance | currency }}
            </td>
            <td class="text-right">
              {{ quote.paid_capital | currency }}
            </td>

            <td>
              <span v-if="quote.status == 0" class="badge badge-pill badge-warning">Pendiente</span>
              <span v-if="quote.status == 1" class="badge badge-pill badge-success">Pagado</span>
            </td>
            <td>
              <button @click="payInstallment(quote)" type="button" class="btn btn-sm btn-success"
                v-if="quote.status == 0">
                Pagar
              </button>
              <button v-else class="btn btn-sm btn-secondary" disabled>
                Pagar
              </button>
            </td>
            <td>
              <div class="input-group mb-3 w-150" v-if="quote.status == 0">
                <input type="number" :min="0" v-model="quote.add_payment" :max="quote.value" step="any"
                  class="form-control form-control-sm" placeholder="Valor" aria-label="Valor"
                  aria-describedby="pay-button" />
                <div class="input-group-append">
                  <button class="btn btn-outline-success btn-sm" @click="payInstallment(quote, quote.add_payment)"
                    type="button" id="pay-button">
                    Abonar
                  </button>
                </div>
              </div>
              <template v-if="quote.add_payment">
                {{ quote.add_payment | currency }}
              </template>
            </td>
            <td v-if="$root.validatePermission('installment-reverse')">
              <button class="btn btn-warning" @click="reversePayment(quote)">
                <i class="bi bi-arrow-counterclockwise"></i>
              </button>
            </td>
            <td>
              <template>
                <div>
                  <textarea class="form-control mb-1" v-model="quote.payment_commitment"
                    placeholder="Anotaciones de promesa de pago"></textarea> <br>
                  <button type="button" v-if="quote.payment_commitment" class="btn btn-success"
                    @click="sendPaymentCommitment(quote)">Enviar</button>
                </div>
              </template>
            </td>
            <td>
              <button v-if="quote.value_pending <= 0 && !quote.status" class="btn btn-success"
                @click="updateStatus(quote)">
                <i class="bi bi-check2-square"></i>
              </button>
              <button v-else class="btn btn-disabled btn-secondary" disabled>
                <i class="bi bi-check2-square"></i>
              </button>
            </td>
          </template>

        </tr>
      </tbody>
    </table>
  </section>
</template>
<script>
export default {
  data() {
    return {
      id_credit: 0,
      listInstallments: [],
      listInstallmentsPaid: [],
      amount_value: 0,
      allow_payment: 0,
      now: new Date().getTime(),
    };
  },
  computed: {},
  methods: {
    listCreditInstallments(credit_id, allow_payment) {
      this.id_credit = credit_id;
      this.allow_payment = allow_payment;

      let me = this;
      axios
        .get(`api/credits/${credit_id}/installments`, me.$root.config)
        .then(function (response) {
          me.listInstallments = response.data;
        });
    },

    payInstallment(quote, amount = null) {
      let late_interests_value = this.listInstallments.filter((x) => x.late_interests_value > 0 && x.status === 0);

      late_interests_value = late_interests_value[0]
        ? late_interests_value[0]["late_interests_value_pending"]
        : 0;

      let me = this;
      var data = {
        amount: amount ? amount : quote.value_pending,
        quote_id: quote.id,
        late_interest_pending: late_interests_value,
      };

      if (quote.value_pending > 0) {
        axios
          .post(
            `api/installment/${quote.credit_id}/pay-installment`,
            data,
            me.$root.config
          )
          .then(function (response) {
            //me.printEntryPdf(response.data.entry_id);
            Swal.fire({
              icon: "success",
              title: ".",
              text: "La operación se ha realizado correctamente",
            });
            me.listCreditInstallments(me.id_credit, 1);
          })
          .catch(function (error) {
            console.log("error", error);
            // handle error
            if (error) {
              Swal.fire({
                icon: "error",
                title: "Oops...",
                text: "No se pudo realizar esta operación",
              });
            }
          })
          .finally
          // me.listCreditInstallments(me.id_credit, 1)
          ();
      } else {
        Swal.fire({
          icon: "error",
          title: "Oops...",
          text: "El valor debe ser mayor a 0 ",
        });
      }
    },

    payCredit(amount_value, new_interest) {
      let late_interests_value = this.listInstallments.filter((x) => x.late_interests_value_pending > 0 && x.status === 0);
      late_interests_value = late_interests_value[0]
        ? late_interests_value[0]["late_interests_value_pending"]
        : 0;

      var data = {
        amount: amount_value,
        late_interest_pending: late_interests_value,
        new_interest: new_interest
      };

      axios
        .post(
          `api/installment/${this.id_credit}/pay-credit`,
          data,
          this.$root.config
        )
        .then((response) => {
          Swal.fire({
            icon: "success",
            title: ".",
            text: "La operación se ha realizado correctamente",
          });
          this.printEntryPdf(response.data.entry_id);
        })
        .catch(function (error) {
          console.log("error", error);
          // handle error
          if (error) {
            Swal.fire({
              icon: "error",
              title: "Oops...",
              text: "No se pudo realizar esta operación",
            });
          }
        })
        .finally(
          this.listCreditInstallments(this.id_credit, this.allow_payment)
        );
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
        console.log(error);
      }
    },

    reversePayment(quote) {
      axios
        .post(
          `api/installment/reverse-payment/${quote.id}`,
          null,
          this.$root.config
        )
        .then(function (response) {
          // handle success
          Swal.fire({
            icon: "success",
            title: ".",
            text: "La operación se ha realizado correctamente",
          });
        })
        .catch(function (error) {
          // handle error
          if (error) {
            Swal.fire({
              icon: "error",
              title: "Oops...",
              text: "No se pudo realizar esta operación",
            });
          }
        })
        .finally(this.listCreditInstallments(this.id_credit, 1));
    },

    updateStatus(quote) {
      axios
        .post(
          `api/installment/change-status/${quote.id}`,
          null,
          this.$root.config
        )
        .then(function (response) {
          // handle success
          Swal.fire({
            icon: "success",
            title: ".",
            text: "La operación se ha realizado correctamente",
          });
        })
        .catch(function (error) {
          // handle error
          if (error) {
            Swal.fire({
              icon: "error",
              title: "Oops...",
              text: "No se pudo realizar esta operación",
            });
          }
        })
        .finally(this.listCreditInstallments(this.id_credit, 1));
    },

    sendPaymentCommitment(quote) {
      console.log(quote)
      let data = {
        'payment_commitment': quote.payment_commitment
      }
      axios
        .post(
          `api/installment/send-payment-commitment/${quote.id}`,
          data,
          this.$root.config
        )
        .then(function (response) {
          // handle success
          Swal.fire({
            icon: "success",
            title: ".",
            text: "La operación se ha realizado correctamente",
          });
        })
        .catch(function (error) {
          // handle error
          if (error) {
            Swal.fire({
              icon: "error",
              title: "Oops...",
              text: "No se pudo realizar esta operación",
            });
          }
        })
        .finally(this.listCreditInstallments(this.id_credit, 1));
    }
  },
};
</script>
