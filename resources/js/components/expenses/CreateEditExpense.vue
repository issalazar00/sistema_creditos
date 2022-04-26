<template>
  <div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title" id="expenseModalLabel">Egresos</h5>
      <button
        type="button"
        class="close"
        data-dismiss="modal"
        aria-label="Close"
      >
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
    <form class="">
      <div class="modal-body">
        <div class="form-group col-12">
          <label for="description">Descripción</label>
          <input
            type="text"
            class="form-control truncate"
            id="description"
            placeholder="Descripción del gasto a realizar"
            v-model="formExpense.description"
          />
          <small id="description_help" class="form-text text-danger">{{
            formErrors.description
          }}</small>
        </div>
        <div class="form-group col-12">
          <label for="date">Fecha</label>
          <input
            type="date"
            class="form-control"
            id="date"
            v-model="formExpense.date"
          />
          <small id="date_help" class="form-text text-danger">{{
            formErrors.date
          }}</small>
        </div>
        <div class="form-row col-12">
          <div class="form-group col-11">
            <label for="type_output">Seleccionar tipo de salida</label>
            <v-select
              :options="expenseTypeList"
              label="description"
              aria-logname="{}"
              :reduce="(expenseType) => expenseType.description"
              v-model="formExpense.type_output"
              placeholder="--Seleccionar--"
            >
            </v-select>
            <small id="type_output_help" class="form-text text-danger">{{
              formErrors.type_output
            }}</small>
          </div>
          <div class="form-group col-1">
            <button
              type="button"
              class="btn btn-success border-success mt-4"
              v-if="!show_type_output"
              @click="show_type_output = true"
            >
              <i class="bi bi-plus-circle"></i>
            </button>
            <button
              type="button"
              class="btn btn-danger border-danger mt-4"
              v-if="show_type_output"
              @click="show_type_output = false"
            >
              <i class="bi bi-x-circle"></i>
            </button>
          </div>
        </div>
        <div class="form-group col-12">
          <create-edit-type-expense
            class="border p-1 bg-light"
            v-if="show_type_output"
            @list-type-expenses="listTypeExpenses(), (show_type_output = false)"
          />
        </div>
        <div class="form-group col-12">
          <label for="price">Precio: <b>{{ formExpense.price | currency}} </b></label>
          <input
            type="number"
            step="any"
            class="form-control"
            id="price"
            placeholder="$"
            v-model="formExpense.price"
          />
          <small id="price_help" class="form-text text-danger">{{
            formErrors.price
          }}</small>
        </div>
      </div>
      <div class="modal-footer">
        <button
          type="button"
          class="btn btn-secondary"
          data-dismiss="modal"
          @click="editar = false"
        >
          Cerrar
        </button>
        <button
          type="button"
          class="btn btn-primary"
          @click="editar ? editExpense() : createExpense()"
        >
          Guardar
        </button>
      </div>
    </form>
  </div>
</template>

<script>
import CreateEditTypeExpense from "./type-expenses/CreateEditTypeExpense.vue";
export default {
  components: { CreateEditTypeExpense },
  data() {
    return {
      editar: false,
      show_type_output: false,
      formExpense: {},
      expenseTypeList: [],
      formErrors: {
        description: "",
        date: "",
        type_output: "",
        price: "",
      },
    };
  },
  methods: {
    createExpense() {
      let me = this;
       me.$root.assignErrors(false, me.formErrors);

      axios
        .post("api/expenses", this.formExpense, me.$root.config)
        .then(function () {
          $("#expenseModal").modal("hide");
          me.resetData();
          me.$emit("list-expenses");
        }).catch(response =>{
              me.$root.assignErrors(response, me.formErrors);
        });
    },
    showEditExpense(expense) {
      this.editar = true;
      let me = this;
      $("#expenseModal").modal("show");
      me.formExpense = expense;
    },
    editExpense() {
      let me = this;
       me.$root.assignErrors(false, me.formErrors);

      axios
        .put(
          `api/expenses/${this.formExpense.id}`,
          this.formExpense,
          me.$root.config
        )
        .then(function () {
          $("#expenseModal").modal("hide");
          me.resetData();
        }).catch(response =>{
              me.$root.assignErrors(response, me.formErrors);
        });
      this.$emit("list-expenses");

      me.editar = false;
    },
    resetData() {
      let me = this;
      Object.keys(this.formExpense).forEach(function (key, index) {
        me.formExpense[key] = "";
      });
      me.$root.assignErrors(false, me.formErrors);
    },
    listTypeExpenses() {
      let me = this;
      axios.get(`api/type-expenses`, me.$root.config).then(function (response) {
        me.expenseTypeList = response.data;
      });
    },
  },
  mounted() {
    this.listTypeExpenses();
  },
};
</script>
