<template>
  <div
    class="modal fade"
    id="payCreditProviderModal"
    tabindex="-1"
    aria-labelledby="payCreditProviderModalLabel"
    aria-hidden="true"
  >
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="payCreditProviderModalLabel">
            Realizar abono a crédito
          </h5>
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
          <form>
            <div class="form-group">
              <label for="credit_value">Valor crédito</label>
              <input
                type="number"
                step="any"
                class="form-control"
                id="credit_value"
                v-model="formCreditProvider.credit_value"
                disabled
                readonly
              />
            </div>
            <div class="form-group">
              <label for="pending_value">Valor pendiente</label>
              <input
                type="number"
                step="any"
                class="form-control"
                id="pending_value"
                v-model="formCreditProvider.pending_value"
                placeholder="$ 0"
                disabled
                readonly
              />
            </div>
            <div class="form-group">
              <label for="add_amount">Abonar: <b>{{ add_amount | currency}} </b></label>
              <input
                type="number"
                step="any"
                class="form-control"
                id="add_amount"
                placeholder="$ 0"
                v-model="add_amount"
                :max="root_data.current_balance_main_box"
                @keyup="
                  add_amount > formCreditProvider.pending_value &&
                  formCreditProvider.pending_value <
                    root_data.current_balance_main_box
                    ? (add_amount = formCreditProvider.pending_value)
                    : (add_amount = add_amount)
                "
              />
              <small id="addAmountHelpBlock" class="form-text text-muted">
                Monto máximo
                {{ root_data.current_balance_main_box | currency }}
              </small>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">
            Cerrar
          </button>
          <button
            type="button"
            class="btn btn-success"
            @click="payCreditProvider()"
          >
            Abonar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      formCreditProvider: {},
      add_amount: 0,
      root_data: this.$root.$data,
    };
  },
  methods: {
    showCreditProvider(credit_provider) {
      this.formCreditProvider = credit_provider;
    },
    payCreditProvider() {
      var data = {
        amount: this.add_amount,
      };
      axios
        .post(
          `api/credit-providers/pay-credit-provider/${this.formCreditProvider.id}`,
          data,
          this.$root.config
        )
        .then(() => {
          $("#payCreditProviderModal").modal("hide");
        });
        this.$emit('list-providers')
    },
  },
};
</script>
