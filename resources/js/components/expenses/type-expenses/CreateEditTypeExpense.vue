<template>
  <form action="">
    <div class="form-group">
      <label for="description">ó crear tipo de salida</label>
      <input
        type="text"
        class="form-control truncate"
        id="description"
        placeholder="Descripción del egreso"
        v-model="formTypeExpense.description"
      />
    </div>
    <button
      type="button"
      class="btn btn-primary"
      @click="editar ? editExpense() : createExpense()"
    >
      Guardar
    </button>
  </form>
</template>

<script>
export default {
  data() {
    return {
      editar: false,
      formTypeExpense: {},
    };
  },
  methods: {
    createExpense() {
      let me = this;
      axios.post("api/type-expenses", this.formTypeExpense, me.$root.config).then(function () {
        me.resetData();
        me.$emit("list-type-expenses");
      });
    },
    resetData() {
      let me = this;
      Object.keys(this.formTypeExpense).forEach(function (key, index) {
        me.formTypeExpense[key] = "";
      });
    },
  },
};
</script>
