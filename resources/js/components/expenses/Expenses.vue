<template>
  <div>
    <div class="page-header d-flex justify-content-between p-4 border my-2">
      <h3>Egresos</h3>
      <button
        type="button"
        class="btn btn-primary"
        data-toggle="modal"
        data-target="#expenseModal"
        v-if="$root.validatePermission('expense-store')"
      >
        Crear Egreso
      </button>
    </div>
    <div class="page-search p-4 border my-2">
      <h6 class="text-primary text-uppercase">Filtrar:</h6>
      <form>
        <div class="form-row">
          <div class="form-group col-md-4 ml-md-auto">
            <label for="search_type_output">Tipo de salida:</label>
            <input
              type="text"
              id="search_type_output"
              name="search_type_output"
              class="form-control"
              placeholder="Escribir el tipo de salida"
              v-model="search_type_output"
            />
          </div>
          <div class="form-group col-md-4 mr-md-auto">
            <label for="search_description">Descripción:</label>
            <input
              type="text"
              id="search_description"
              name="search_description"
              class="form-control"
              placeholder="Escribir el motivo o descripción"
              v-model="search_description"
            />
          </div>
        </div>
        <div class="form-row">
          <div class="form-group col-md-4 ml-auto">
            <label for="">Desde:</label>
            <input
              type="date"
              id="search_from"
              name="search_from"
              class="form-control"
              placeholder="Desde"
              v-model="search_from"
              :max="now"
            />
          </div>
          <div class="form-group col-md-4 mr-auto">
            <label for="">Hasta:</label>
            <input
              type="date"
              id="search_to"
              name="search_to"
              class="form-control"
              placeholder="Desde"
              v-model="search_to"
              :max="now"
            />
          </div>
        </div>
        <div class="form-row text-right m-auto">
          <div class="form-group m-md-auto col-md-4">
            <button
              class="btn btn-success w-100"
              type="button"
              @click="listExpenses(1)"
            >
              <i class="bi bi-search"></i> Buscar
            </button>
          </div>
        </div>
      </form>
    </div>
    <div class="page-content">
      <section class="table-responsive">
        <table class="table table-bordered table-sm">
          <thead>
            <tr class="text-center">
              <th>Sede</th>
              <th>Responsable</th>
              <th>Fecha</th>
              <th>Tipo de Salida</th>
              <th>Descripción</th>
              <th>Valor</th>
              <th v-if="$root.validatePermission('expense-status')">Estado</th>
              <th>Ver factura</th>
              <th v-if="$root.validatePermission('expense-update')">
                Opciones
              </th>
            </tr>
          </thead>
          <tbody v-if="expenseList.data && expenseList.data.length > 0">
            <tr v-for="e in expenseList.data" :key="e.id">
              <td>{{ e.headquarter.headquarter }}</td>
              <td>{{ e.user.name }} {{ e.user.last_name }}</td>
              <td>{{ e.date }}</td>
              <td>{{ e.type_output }}</td>
              <td class="wrap">
                <textarea
                  name=""
                  class="form-control-plaintext"
                  readonly
                  id=""
                  cols="7"
                  rows="4"
                  v-model="e.description"
                >
                </textarea>
                </td>
              <td class="text-right">{{ e.price | currency }}</td>
              <td
                class="text-right"
                v-if="$root.validatePermission('expense-status')"
              >
                <button
                  v-if="e.status == 0"
                  class="btn btn-danger"
                  @click="changeStatus(e.id)"
                >
                  <i class="bi bi-x-circle"></i>
                </button>
                <button
                  v-if="e.status == 1"
                  class="btn btn-success"
                  @click="changeStatus(e.id)"
                >
                  <i class="bi bi-check2-circle"></i>
                </button>
              </td>
              <td class="text-right">
                <button
                  class="btn btn-info"
                  type="button"
                  @click="printExpendePdf(e.id)"
                >
                  <i class="bi bi-eye"></i>
                </button>
              </td>
              <td
                class="text-right"
                v-if="$root.validatePermission('expense-update')"
              >
                <button
                  v-if="e.status == 1"
                  class="btn btn-success"
                  @click="showData(e)"
                >
                  <i class="bi bi-pen"></i>
                </button>
                <button v-else class="btn btn-secondary" disabled>
                  <i class="bi bi-pen"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        <pagination
          :align="'center'"
          :data="expenseList"
          :limit="2"
          @pagination-change-page="listExpenses"
        >
          <span slot="prev-nav"><i class="bi bi-chevron-double-left"></i></span>
          <span slot="next-nav"
            ><i class="bi bi-chevron-double-right"></i
          ></span>
        </pagination>
      </section>
    </div>
    <modal-expense ref="ModalExpense" @list-expenses="listExpenses(1)" />
  </div>
</template>

<script>
import ModalExpense from "./ModalExpense.vue";
export default {
  components: { ModalExpense },
  data() {
    return {
      expenseList: {},
      search_from: "",
      search_to: "",
      search_description: "",
      search_type_output: "",
      now: new Date().toISOString().slice(0, 10),
    };
  },
  mounted() {
    this.listExpenses(1);
  },
  methods: {
    listExpenses(page = 1) {
      let me = this;
      axios
        .get(
          `api/expenses?page=${page}&from=${this.search_from}&to=${this.search_to}&type_output=${this.search_type_output}&description=${this.search_description}`,
          me.$root.config
        )
        .then(function (response) {
          me.expenseList = response.data;
        });
    },
    showData: function (expense) {
      this.$refs.ModalExpense.showEditExpense(expense);
    },
    changeStatus: function (id) {
      let me = this;

      Swal.fire({
        title: "¿Quieres cambiar el estado de este egreso?",
        showDenyButton: true,
        denyButtonText: `Cancelar`,
        confirmButtonText: `Guardar`,
      }).then((result) => {
        if (result.isConfirmed) {
          axios
            .post(`api/expenses/${id}/change-status`, null, me.$root.config)
            .then(function () {
              me.listExpenses(1);
            });
          Swal.fire("Cambios realizados!", "", "success");
        } else if (result.isDenied) {
          Swal.fire("Operación no realizada", "", "info");
        }
      });
    },

    printExpendePdf(expense_id) {
      axios
        .get(`api/expenses/show-expense/${expense_id}`, this.$root.config)
        .then((response) => {
          const pdf = response.data.pdf;
          var a = document.createElement("a");
          a.href = "data:application/pdf;base64," + pdf;
          a.download = `egreso_${expense_id}-${Date.now()}.pdf`;
          a.click();
        });
    },
  },
};
</script>
