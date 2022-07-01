<template>
  <div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title" id="formClientModalLabel">Clientes</h5>
      <button
        type="button"
        class="close"
        data-dismiss="modal"
        @click="(editar = false), resetData()"
        aria-label="Close"
      >
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
    <div class="modal-body">
      <form>
        <div class="form-row">
          <div class="form-group col-md-4">
            <label for="name">Nombres</label>
            <input
              type="text"
              class="form-control"
              id="name"
              v-model="formClient.name"
              placeholder="Ingresar nombres"
              :class="[formErrors.name ? 'is-invalid' : '']"
            />
            <small id="name_help" class="form-text text-danger">
              {{ formErrors.name }}
            </small>
          </div>
          <div class="form-group col-md-4">
            <label for="Apellidos">Apellidos</label>
            <input
              type="text"
              class="form-control"
              id="last_name"
              v-model="formClient.last_name"
              placeholder="Ingresar  apellidos"
              :class="[formErrors.last_name ? 'is-invalid' : '']"
            />
            <small id="last_name_help" class="form-text text-danger">
              {{ formErrors.last_name }}
            </small>
          </div>
          <div class="form-group col-md-4">
            <label for="birth_date">Fecha nacimiento</label>
            <input
              type="date"
              class="form-control"
              id="birth_date"
              v-model="formClient.birth_date"
              :class="[formErrors.birth_date ? 'is-invalid' : '']"
            />
            <small id="birth_date_help" class="form-text text-danger">
              {{ formErrors.birth_date }}
            </small>
          </div>
          <div class="form-group col-md-4">
            <label for="type_document">Tipo Documento</label>
            <select
              name="type_document"
              id="type_document"
              class="custom-select"
              v-model="formClient.type_document"
              placeholder="--Selecciona--"
              :class="[formErrors.type_document ? 'is-invalid' : '']"
            >
              <option value="" disabled>--Seleccionar--</option>
              <option
                v-for="(d, key) in type_documents"
                :key="key"
                :value="key"
              >
                {{ d }}
              </option>
              <small id="type_document_help" class="form-text text-danger">
                {{ formErrors.type_document }}
              </small>
            </select>
          </div>
          <div class="form-group col-md-4">
            <label for="Apellidos">Nro. Documento</label>

            <input
              type="text"
              class="form-control"
              id="Documento"
              v-model="formClient.document"
              placeholder="Ingresar identificación"
              :class="[formErrors.document ? 'is-invalid' : '']"
            />
            <small id="document_help" class="form-text text-danger">
              {{ formErrors.document }}
            </small>
          </div>

          <div class="form-group col-md-4">
            <label for="civil_status">Estado civil</label>
            <select
              name="civil_status"
              id="civil_status"
              class="custom-select"
              v-model="formClient.civil_status"
              :class="[formErrors.civil_status ? 'is-invalid' : '']"
            >
              <option value="" disabled>--Seleccionar--</option>
              <option value="Soltero">Soltero</option>
              <option value="Casado">Casado</option>
              <option value="Union libre">Union libre</option>
              <option value="Divorciado">Divorciado</option>
              <option value="Viudo">Viudo</option>
            </select>
            <small id="civil_status_help" class="form-text text-danger">
              {{ formErrors.civil_status }}
            </small>
          </div>

          <div class="form-group">
            <label for="gender">Género</label>
            <br />
            <div class="form-check form-check-inline">
              <input
                class="form-check-input"
                type="radio"
                name="inlineRadioOptions"
                id="Hombre"
                value="M"
                v-model="formClient.gender"
              />
              <label class="form-check-label" for="Hombre">Hombre</label>
            </div>
            <div class="form-check form-check-inline">
              <input
                class="form-check-input"
                type="radio"
                name="inlineRadioOptions"
                id="Mujer"
                value="F"
                v-model="formClient.gender"
              />
              <label class="form-check-label" for="Mujer">Mujer</label>
            </div>
            <div class="form-check form-check-inline">
              <input
                class="form-check-input"
                type="radio"
                name="inlineRadioOptions"
                id="Otro"
                value="O"
                v-model="formClient.gender"
              />
              <label class="form-check-label" for="Otro">Otro</label>
            </div>
            <small id="gender_help" class="form-text text-danger">
              {{ formErrors.gender }}
            </small>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group col-4">
            <label for="email">Correo Electronico</label>
            <input
              type="email"
              class="form-control"
              id="email"
              v-model="formClient.email"
              placeholder="Ingresar correo electrónico"
              :class="[formErrors.email ? 'is-invalid' : '']"
            />
            <small id="email_help" class="form-text text-danger">
              {{ formErrors.email }}
            </small>
          </div>
          <div class="form-group col-4">
            <label for="address">Dirección</label>
            <input
              type="text"
              class="form-control"
              id="address"
              v-model="formClient.address"
              placeholder="Ingresar dirección"
              :class="[formErrors.address ? 'is-invalid' : '']"
            />
            <small id="address_help" class="form-text text-danger">
              {{ formErrors.address }}
            </small>
          </div>
          <div class="form-group col-4">
            <label for="phone_1">Celular 1</label>
            <input
              type="tel"
              class="form-control"
              id="phone_1"
              v-model="formClient.phone_1"
              placeholder="Ingresar numero de celular"
              :class="[formErrors.phone_1 ? 'is-invalid' : '']"
            />
            <small id="phone_1_help" class="form-text text-danger">
              {{ formErrors.phone_1 }}
            </small>
          </div>
          <div class="form-group col-4">
            <label for="phone_2">Celular 2</label>
            <input
              type="tel"
              class="form-control"
              id="phone_2"
              v-model="formClient.phone_2"
              placeholder="Ingresar numero de celular"
              :class="[formErrors.phone_2 ? 'is-invalid' : '']"
            />
            <small id="phone_2_help" class="form-text text-danger">
              {{ formErrors.phone_2 }}
            </small>
          </div>

          <div class="form-group col-md-4">
            <label for="workplace">Lugar de trabajo</label>
            <input
              type="email"
              class="form-control"
              id="workplace"
              v-model="formClient.workplace"
              placeholder="Ingresar lugar de trabajo"
              :class="[formErrors.workplace ? 'is-invalid' : '']"
            />
            <small id="workplace_help" class="form-text text-danger">
              {{ formErrors.workplace }}
            </small>
          </div>
          <div class="form-group col-md-4">
            <label for="occupation">Cargo</label>
            <input
              type="text"
              class="form-control"
              id="occupation"
              v-model="formClient.occupation"
              placeholder="Ingresar ocupación"
              :class="[formErrors.occupation ? 'is-invalid' : '']"
            />
            <small id="occupation_help" class="form-text text-danger">
              {{ formErrors.occupation }}
            </small>
          </div>
          <div class="form-check col-md-4 ml-4">
            <input
              class="form-check-input"
              type="checkbox"
              value="1"
              id="independent"
              v-model="formClient.independent"
            />
            <label class="form-check-label" for="independent">
              Independiente
            </label>
            <small id="independent_help" class="form-text text-danger">
              {{ formErrors.independent }}
            </small>
          </div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button
        type="button"
        class="btn btn-secondary"
        data-dismiss="modal"
        @click="(editar = false), resetData()"
      >
        Cerrar
      </button>
      <button
        type="button"
        class="btn btn-success"
        @click="formClient.id ? editClient() : createClient()"
      >
        Guardar
      </button>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      editar: false,
      formClient: {
        name: "",
        last_name: "",
        type_document: "CC",
        document: 0,
        birth_date: "",
        email: "",
        phone_1: "",
        phone_2: "",
        gender: "",
        civil_status: "",
        independent: 0,
        workplace: "",
        occupation: "",
        address: "",
      },
      formErrors: {
        name: "",
        last_name: "",
        type_document: "",
        document: "",
        birth_date: "",
        email: "",
        phone_1: "",
        phone_2: "",
        gender: "",
        civil_status: "",
        independent: "",
        workplace: "",
        occupation: "",
        address: "",
      },
      type_documents: this.$root.$data.type_documents,
    };
  },
  methods: {
    createClient() {
      let me = this;
       me.$root.assignErrors(false, me.formErrors);

      axios
        .post("api/clients", this.formClient, me.$root.config)
        .then(function () {
          $("#formClientModal").modal("hide");
          me.resetData();
          me.$emit("list-clients");
        }).catch(response => {
          me.$root.assignErrors(response, me.formErrors);
        });
    },
    showEditClient(client) {
      this.editar = true;
      let me = this;
      $("#formClientModal").modal("show");
      me.formClient = Object.assign({}, client);
    },
    editClient() {
      let me = this;
      me.$root.assignErrors(false, me.formErrors);

      axios
        .put(
          `api/clients/${this.formClient.id}`,
          this.formClient,
          me.$root.config
        )
        .then(function () {
          $("#formClientModal").modal("hide");
          me.resetData();
          me.$emit("list-clients");
        }).catch(response => {
          me.$root.assignErrors(response, me.formErrors);
        });

      
      me.editar = false;
    },
    resetData() {
      let me = this;
      Object.keys(this.formClient).forEach(function (key, index) {
        me.formClient[key] = "";
      });
      me.$root.assignErrors(false, me.formErrors);
    },
  },
};
</script>
