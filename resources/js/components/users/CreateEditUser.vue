<template>
  <div>
    <div
      class="modal fade"
      id="formUserModal"
      tabindex="-1"
      aria-labelledby="formUserModalLabel"
      aria-hidden="true"
    >
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="formUserModalLabel">
              Gestionar usuario
            </h5>
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
                  <label for="nombre">Nombre</label>
                  <input
                    type="text"
                    class="form-control"
                    id="nombre"
                    v-model="formUser.name"
                    placeholder="Ingresar nombre"
                  />
                  <small id="name_help" class="form-text text-danger">{{
                    formErrors.name
                  }}</small>
                </div>
                <div class="form-group col-md-4">
                  <label for="name">Apellidos</label>
                  <input
                    type="text"
                    class="form-control"
                    id="name"
                    v-model="formUser.last_name"
                    placeholder="Ingresar apellidos"
                  />
                  <small id="last_name_help" class="form-text text-danger">{{
                    formErrors.last_name
                  }}</small>
                </div>
                <div class="form-group col-md-4">
                  <label for="email">Email</label>
                  <input
                    type="email"
                    class="form-control"
                    id="email"
                    v-model="formUser.email"
                    placeholder="Ingresar email"
                  />
                  <small id="email_help" class="form-text text-danger">{{
                    formErrors.email
                  }}</small>
                </div>
                <div class="form-group col-md-4">
                  <label for="email">Username</label>
                  <input
                    type="text"
                    class="form-control"
                    id="username"
                    v-model="formUser.username"
                    placeholder="Ingresar username"
                  />
                  <small id="username_help" class="form-text text-danger">{{
                    formErrors.username
                  }}</small>
                </div>
                <div class="form-group col-md-4">
                  <label for="phone">Celular</label>
                  <input
                    type="number"
                    class="form-control"
                    id="phone"
                    v-model="formUser.phone"
                    placeholder="Ingresar celular"
                  />
                  <small id="phone_help" class="form-text text-danger">{{
                    formErrors.phone
                  }}</small>
                </div>
                <div class="form-group col-md-4">
                  <label for="type_document">Tipo Documento</label>
                  <select
                    name="type_document"
                    id="type_document"
                    class="custom-select"
                    v-model="formUser.type_document"
                  >
                    <option value="" disabled selected>--Seleccionar--</option>
                    <option
                      v-for="(d, key) in type_documents"
                      :key="key"
                      :value="key"
                    >
                      {{ d }}
                    </option>
                  </select>
                  <small
                    id="type_document_help"
                    class="form-text text-danger"
                    >{{ formErrors.type_document }}</small
                  >
                </div>
                <div class="form-group col-md-4">
                  <label for="document">Nro. Documento</label>
                  <input
                    type="text"
                    class="form-control"
                    id="document"
                    v-model="formUser.document"
                    placeholder="Ingresar identificación"
                  />
                  <small id="document_help" class="form-text text-danger">{{
                    formErrors.document
                  }}</small>
                </div>
                <div class="form-group col-md-4">
                  <label for="address">Dirección</label>

                  <input
                    type="text"
                    class="form-control"
                    id="address"
                    v-model="formUser.address"
                    placeholder="Ingresar dirección"
                  />
                  <small id="address_help" class="form-text text-danger">{{
                    formErrors.address
                  }}</small>
                </div>
                <div class="form-group col-md-4">
                  <label for="headquarter_id">Sede</label>
                  <v-select
                    :options="headquarterList.data"
                    :label="'headquarter'"
                    :reduce="(headquarter) => headquarter.id"
                    v-model="formUser.headquarter_id"
                    placeholder="--Seleccionar sede--"
                  >
                  </v-select>
                  <small
                    id="headquarter_id_help"
                    class="form-text text-danger"
                    >{{ formErrors.headquarter_id }}</small
                  >
                </div>
                <div class="form-group col-md-4">
                  <label for="rol">Rol</label>
                  <select
                    name="rol"
                    id="rol"
                    class="custom-select"
                    v-model="formUser.rol"
                  >
                    <option value="" disabled selected>--Seleccionar--</option>
                    <option
                      v-for="rol in listRoles"
                      :value="rol.id"
                      :key="rol.id"
                    >
                      {{ rol.name }}
                    </option>
                  </select>
                  <small id="rol_id_help" class="form-text text-danger">{{
                    formErrors.rol
                  }}</small>
                </div>
                <div class="form-group col-md-4">
                  <label for="password">Contraseña</label>
                  <input
                    type="password"
                    class="form-control"
                    id="password"
                    name="password"
                    placeholder="Ingresar contraseña"
                    v-model="formUser.password"
                    pattern="^(?=\w*\d)(?=\w*[A-Z])(?=\w*[a-z])\S{8,16}$"
                    required
                  />
                  <small id="password_help" class="form-text text-danger">{{
                    formErrors.password
                  }}</small>
                </div>
                <div class="form-group col-md-4">
                  <label for="password">Confirmar contraseña</label>
                  <input
                    type="password"
                    class="form-control"
                    id="password_confirmation"
                    name="password_confirmation"
                    placeholder="Ingresar contraseña"
                    v-model="formUser.password_confirmation"
                    pattern="^(?=\w*\d)(?=\w*[A-Z])(?=\w*[a-z])\S{8,16}$"
                    required
                  />
                  <small id="passwordHelp" class="form-text text-danger">{{
                    formErrors.password
                  }}</small>
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button
              type="button"
              class="btn btn-primary"
              @click="formUser.id ? editUser() : createUser()"
            >
              Guardar
            </button>

            <button
              type="button"
              class="btn btn-secondary"
              data-dismiss="modal"
              @click="editar = false"
            >
              Cerrar
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      editar: false,
      headquarterList: [],
      formUser: {
        name: "",
        last_name: "",
        email: "",
        username: "",
        password: "",
        phone: "",
        address: "",
        type_document: "CC",
        document: 0,
        photo: "",
        headquarter_id: "",
        rol: "",
      },
      formErrors: {
        name: "",
        last_name: "",
        email: "",
        username: "",
        password: "",
        phone: "",
        address: "",
        type_document: "",
        document: "",
        photo: "",
        headquarter_id: "",
        rol: "",
      },
      type_documents: this.$root.$data.type_documents,
      listRoles: [],
    };
  },
  // Function createUsers
  created() {
    this.listHeadquarters(1);
    this.getRoles();
  },
  methods: {
    getRoles() {
      axios.get("api/roles/getAllRoles", this.$root.config).then((response) => {
        this.listRoles = response.data.roles;
      });
    },
    createUser() {
      let me = this;
      me.$root.assignErrors(false, me.formErrors);

      axios
        .post("api/users", me.formUser, me.$root.config)
        .then(function () {
          $("#formUserModal").modal("hide");
          me.resetData();
          me.$emit("list-users");
        })
        .catch((response) => {
          me.$root.assignErrors(response, me.formErrors);
        });
    },
    showEditarUser(user) {
      this.editar = true;
      let me = this;
      $("#formUserModal").modal("show");

      Object.keys(user).forEach(function (key, index) {
        me.formUser[key] = user[key];
      });
      me.formUser.rol = user.roles.length > 0 ? user.roles[0].id : "";
    },
    editUser() {
      let me = this;
      me.$root.assignErrors(false, me.formErrors);
      axios.put("api/users/" + me.formUser.id, me.formUser, me.$root.config)
        .then(function (res) {
          $("#formUserModal").modal("hide");
          me.resetData();
          me.$emit("list-users");
        })
        .catch((response) => {
          me.$root.assignErrors(response, me.formErrors);
        });
      this.editar = false;
    },
    resetData() {
      let me = this;
      Object.keys(this.formUser).forEach(function (key, index) {
        me.formUser[key] = "";
      });
      me.$root.assignErrors(false, me.formErrors);
    },

    listHeadquarters(page = 1) {
      let me = this;
      axios
        .get(`api/headquarters?page=${page}`, me.$root.config)
        .then(function (response) {
          me.headquarterList = response.data;
        });
    },
  },
};
</script>
