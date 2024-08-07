<template>
  <div>
    <div class="page-header d-flex justify-content-between p-4 border my-2">
      <h3 class="col-4">Egresos</h3>
      <ul class="list-group col-4">
        <li class="list-group-item">
          <h5 class="text-dark font-weight-bold">Total egresos: {{ expensesTotal.price | currency }}</h5>
        </li>
      </ul>
      <button type="button" class="btn btn-primary col-4" data-toggle="modal" data-target="#expenseModal"
        v-if="$root.validatePermission('expense-store')">
        Crear Egreso
      </button>
    </div>
    <div class="page-search p-4 border my-2">
      <h6 class="text-primary text-uppercase">Filtrar:</h6>
      <form>
        <div class="form-row">
          <div class="form-group col-md-4">
            <label for="search_id">Nro. de egreso:</label>
            <input type="text" id="search_id" name="search_id" class="form-control" placeholder="Nro de egreso"
              v-model="search_id" />
          </div>
          <div class="form-group col-md-4 ml-md-auto">
            <label for="headquarter_id">Sede</label>
            <v-select :options="headquarterList" label="headquarter" aria-logname="{}"
              :reduce="(headquarter) => headquarter.id" v-model="search_headquarter_id" placeholder="Seleccionar sede">
            </v-select>
          </div>
          <div class="form-group col-md-4 ml-md-auto">
            <label for="search_type_movement">Tipo de salida:</label>
            <input type="text" id="search_type_movement" name="search_type_movement" class="form-control"
              placeholder="Escribir el tipo de movmiento" v-model="search_type_movement" />
          </div>
          <div class="form-group col-md-4 ml-md-auto">
            <label for="search_type_output">Tipo de salida:</label>
            <input type="text" id="search_type_output" name="search_type_output" class="form-control"
              placeholder="Escribir el tipo de salida" v-model="search_type_output" />
          </div>
          <div class="form-group col-md-4 mr-md-auto">
            <label for="search_description">Descripción:</label>
            <input type="text" id="search_description" name="search_description" class="form-control"
              placeholder="Escribir el motivo o descripción" v-model="search_description" />
          </div>
        </div>
        <div class="form-row">
          <div class="form-group col-md-4 col-sm-6 col-xs-6 ml-auto">
            <label for="">Desde:</label>
            <input type="date" id="search_from" name="search_from" class="form-control" placeholder="Desde"
              v-model="search_from" :max="now" />
          </div>
          <div class="form-group col-md-4 col-sm-6 col-xs-6">
            <label for="">Hasta:</label>
            <input type="date" id="search_to" name="search_to" class="form-control" placeholder="Desde"
              v-model="search_to" :max="now" />
          </div>
          <div class="form-group col-md-4 col-sm-6 col-xs-6 col-sm-6 col-xs-6  mr-md-auto">
            <label for="">Mostrar {{ search_results }} resultados por página:</label>
            <input type="number" id="search_results" name="search_results" class="form-control" placeholder="Desde"
              v-model="search_results" max="1000" />
          </div>
        </div>
        <div class="form-row text-right m-auto">
          <div class="form-group m-md-auto col-md-4">
            <button class="btn btn-success w-100" type="button" @click="listExpenses(1)">
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
              <th>Tipo de movimiento</th>
              <th>Tipo de Salida</th>
              <th>Descripción</th>
              <th>Valor</th>
              <th>Ver factura</th>
              <th v-if="$root.validatePermission('expense-delete')">Eliminar</th>
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
              <td>{{ e.type_movement }}</td>
              <td>{{ e.type_output }}</td>
              <td class="wrap">
                <textarea name="" class="form-control-plaintext" readonly id="" cols="7" rows="4"
                  v-model="e.description">
                </textarea>
              </td>
              <td class="text-right">{{ e.price | currency }}</td>
              <td class="text-right">
                <button class="btn btn-danger btn-block" type="button" @click="printExpensePdf(e.id)">
                  <i class="bi bi-file-pdf-fill"></i> PDF
                </button>
                <br>
                <button class="btn btn-success btn-block" type="button" @click="printExpenseTicket(e.id)">
                  <i class="bi bi-receipt-cutoff"></i> Ticket
                </button>
              </td>
              <td class="text-right" v-if="$root.validatePermission('expense-delete')">
                <button class="btn btn-danger" @click="deleteExpense(e.id)">
                  <i class="bi bi-trash"></i>
                </button>

              </td>
              <td class="text-right" v-if="$root.validatePermission('expense-update')">
                <button class="btn btn-success" @click="showData(e)">
                  <i class="bi bi-pen"></i>
                </button>
                <!-- <button class="btn btn-secondary" disabled>
                  <i class="bi bi-pen"></i>
                </button> -->
              </td>
            </tr>
          </tbody>
        </table>
        <pagination :align="'center'" :data="expenseList" :limit="2" @pagination-change-page="listExpenses">
          <span slot="prev-nav"><i class="bi bi-chevron-double-left"></i></span>
          <span slot="next-nav"><i class="bi bi-chevron-double-right"></i></span>
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
      expensesTotal: {},
      headquarterList: [],
      search_from: "",
      search_to: "",
      search_id: "",
      search_description: "",
      search_type_output: "",
      search_type_movement: "",
      search_headquarter_id: 'all',
      search_results: 15,
      now: new Date().toISOString().slice(0, 10),
    };
  },
  mounted() {
    this.listExpenses(1);
    this.listHeadquarters();
  },

  methods: {
    listExpenses(page = 1) {

      let data = {
        page: page,
        id: this.search_id,
        from: this.search_from,
        to: this.search_to,
        type_output: this.search_type_output,
        type_movement: this.search_type_movement,
        description: this.search_description,
        results: this.search_results,
        headquarter_id: this.search_headquarter_id
      };

      let me = this;

      axios
        .get(
          `api/expenses`, {
          params: data,
          headers: this.$root.config.headers,
        })
        .then(function (response) {
          me.expenseList = response.data.expenses;
          me.expensesTotal = response.data.totals;
        });
    },
    listHeadquarters() {
      let me = this;
      axios
        .get(`api/headquarters/list-all-headquarters`, me.$root.config)
        .then(function (response) {
          me.headquarterList = response.data;
        });
    },
    showData: function (expense) {
      this.$refs.ModalExpense.showEditExpense(expense);
    },

    deleteExpense: function (id) {
      let me = this;

      Swal.fire({
        title: "¿Quieres cambiar el estado de este egreso?",
        text: "Recuerda que esta operación no se puede deshacer",
        showDenyButton: true,
        denyButtonText: `Cancelar`,
        confirmButtonText: `Guardar`,
      }).then((result) => {
        if (result.isConfirmed) {
          axios
            .delete(`api/expenses/${id}`, me.$root.config)
            .then(function () {
              me.listExpenses(1);
            });
          Swal.fire("Cambios realizados!", "", "success");
        } else if (result.isDenied) {
          Swal.fire("Operación no realizada", "", "info");
        }
      });
    },

    printExpensePdf(expense_id) {
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
    printExpenseTicket(id) {
      axios.get(`api/print-expense/${id}`, this.$root.config);
    },
  },
};
</script>
