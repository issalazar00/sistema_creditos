<template>
  <div
    class="modal fade"
    id="creditInformationModal"
    tabindex="-1"
    aria-labelledby="creditInformationModalLabel"
    aria-hidden="true"
  >
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="creditInformationModalLabel">
            Información general crédito
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
          <section class="table-responsive">
            <table
              class="table table-bordered table-condensed"
              v-if="
                CreditInformation != null && CreditInformation.client_id != null
              "
            >
              <tr class="text-center">
                <td>
                  <strong>Cliente:</strong> <br />
                  {{ CreditInformation.name }}
                  {{ CreditInformation.last_name }}
                </td>
                <td>
                  <strong> Identificacion: </strong> <br />
                  {{ CreditInformation.type_document }}
                  {{ CreditInformation.document }}
                </td>
                <td>
                  <strong>Contacto:</strong> <br />
                  {{ CreditInformation.phone_1 }} -
                  {{ CreditInformation.phone_2 }}
                </td>
              </tr>
              <tr class="text-center">
                <td>
                  <strong># Crédito: </strong>
                  {{ CreditInformation.id }}
                </td>
                <td>
                  <strong>Valor: </strong>
                  {{ CreditInformation.credit_value | currency }}
                </td>
                <td>
                  <strong>Nro cuotas: </strong>
                  {{ CreditInformation.number_installments }}
                </td>
              </tr>
              <tr class="text-center">
                 <td>
                  <strong>Sede: </strong>

                  {{ CreditInformation.headquarter.headquarter }}
                </td>
                <td colspan="2">
                  <strong>Descripción: </strong>

                  {{ CreditInformation.description }}
                </td>
              </tr>
              <tr
                v-if="
                  CreditInformation.debtor_id != null &&
                  CreditInformation.debtor_id != 0 &&
                  debtor_info != 'undefined'
                "
              >
                <td>
                  <strong>Codeudor: </strong> <br />
                  {{ debtor_info.name }}
                  {{ debtor_info.last_name }}
                </td>
                <td>
                  <strong>Identificación: </strong><br />
                  {{ debtor_info.type_document }}
                  {{ debtor_info.document }}
                </td>
                <td>
                  <strong>Contacto:</strong> <br />
                  {{ debtor_info.phone_1 }} -
                  {{ debtor_info.phone_2 }}
                </td>
              </tr>
              <tr
                v-if="
                  CreditInformation.provider_id != null &&
                  CreditInformation.provider_id != 0 &&
                  provider_info != 'undefined'
                "
              >
                <td>
                  <strong>Proveedor :</strong> <br />
                  {{ provider_info.business_name }}
                </td>
                <td>
                  <strong>Identificación: </strong><br />
                  {{ provider_info.type_document }}
                  {{ provider_info.document }}
                </td>
                <td>
                  <strong>Contacto:</strong> <br />
                  {{ provider_info.phone_1 }} -
                  {{ provider_info.phone_2 }}
                </td>
              </tr>
            </table>
          </section>
          <section>
            <installment ref="Installment" />
          </section>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">
            Cerrar
          </button>
          <button
            type="button"
            class="btn btn-success"
            @click="changeStatus(CreditInformation.id, 1)"
          >
            Aprobar
          </button>
          <button
            type="button"
            class="btn btn-danger"
            @click="changeStatus(CreditInformation.id, 2)"
          >
            Rechazar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Installment from "../credit_helpers/Installment.vue";
export default {
  components: {
    Installment,
  },
  data() {
    return {
      CreditInformation: {},
      debtor_info: {},
      provider_info: {},
    };
  },
  methods: {
    showInformation(credit) {
      this.CreditInformation = credit;
      this.showInstallments(credit.id);
      if (credit.provider_id != null || credit.debtor_id != null) {
        this.consultAdditionalInfoCredit(credit.id);
      }
    },
    showInstallments(credit_id) {
      this.$refs.Installment.listCreditInstallments(credit_id, 0);
    },
    consultAdditionalInfoCredit(credit_id) {
      axios
        .get(`api/credits/general-information/${credit_id}`, this.$root.config)
        .then((response) => {
          this.provider_info = response.data.provider;
          this.debtor_info = response.data.debtor;
        });
    },
    changeStatus: function (id, status) {
      Swal.fire({
        title: "¿Quieres cambiar el status del credito?",
        showCancelButton: true,
        cancelButtonText: "Cancelar",
        confirmButtonText: `Aceptar`,
      }).then((result) => {
        if (result.isConfirmed) {
          if (status != 2) {
            this.sendData(id, data);
            Swal.fire("Cambios realizados!", "", "success");
          } else {
            this.msgRejectd(id);
          }
        } else {
          Swal.fire("Operación no realizada", "", "info");
        }
      });
    },
    msgRejectd: async function (id) {
      const swalWithBootstrapButtons = Swal.mixin({
        customClass: {
          title: "text-primary",
          cancelButton: "btn btn-secondary",
          confirmButton: "btn btn-danger",
        },
        buttonsStyling: false,
      });

      await swalWithBootstrapButtons
        .fire({
          title: "Motivo de rechazo",
          reverseButtons: true,
          input: "text",
          inputLabel: "Realice una descripción del motivo",
          inputPlaceholder: "",
          showCancelButton: true,
          cancelButtonText: "Cancelar",
          confirmButtonText: "Rechazar",
          inputValidator: (value) => {
            if (!value) {
              return "Debes completar este campo!";
            }
          },
        })
        .then((response) => {
          console.log(response.isConfirmed);
          if (response.isConfirmed) {
            var data = {
              status: 2,
              description: response.value,
            };
            this.sendData(id, data);
            Swal.fire("Cambios realizados!", "", "success");
          } else {
            Swal.fire("Operación no realizada", "", "info");
          }
        });
    },
    sendData(id, data) {
      let me = this;
      axios
        .post(`api/credits/${id}/change-status`, data, this.$root.config)
        .then(function () {
          me.listCredits(1);
        });
    },
  },
};
</script>
