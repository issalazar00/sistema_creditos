<template>
  <div>
    <div class="page-header d-flex justify-content-between p-4 border my-2">
      <h3 class="col-6">Reporte de egresos por sede</h3>
      <ul class="list-group col-6">
        <li class="list-group-item"><h5 class="text-dark font-weight-bold">Total egresos: {{headquartersExpenseTotal.price | currency}}</h5></li>
      </ul>
    </div>
    <div class="page-search p-4 border my-2">
      <h6 class="text-primary text-uppercase">Filtrar:</h6>
      <form>
        <div class="form-row">
          <div class="form-group col-12 col-md-3">
            <label for="search_from">Desde:</label>
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
          <div class="form-group col-12 col-md-3">
            <label for="search_to">Hasta:</label>
            <input
              type="date"
              id="search_to"
              name="search_to"
              class="form-control"
              placeholder="Hasta"
              v-model="search_to"
              :max="now"
            />
          </div>
          <div class="form-group col-12 col-md-3">
            <label for="search_type_movement">Tipo de movimiento:</label>
            <v-select
              :options="['GASTO','EGRESO']"
              label="search_type_movement"
              aria-logname="{}"
              v-model="search_type_movement"
              placeholder="--Seleccionar--"
            > </v-select>
           
          </div>
          <div class="form-group col-12 col-md-3">
            <label for="search_type_output">Tipo de salida:</label>
            <input
              type="text"
              id="search_type_output"
              name="search_type_output"
              class="form-control"
              placeholder="Tipo de salida"
              v-model="search_type_output"
            />
          </div>
          <div class="form-group col-12 col-md-3">
						<label for="user">Usuario
						</label>
						<v-select label="name" v-model="search_user_id"
							:reduce="(option) => option.id" :filterable="false" :options="listUsers"
							@search="onSearchUsers">
							<template slot="no-options">
								Escribe para iniciar la búsqueda
							</template>
							<template slot="option" slot-scope="option">
								<div class="d-center">
									{{ option.name }} {{ option.last_name }}
								</div>
							</template>
							<template slot="selected-option" slot-scope="option">
								<div class="selected d-center">
									{{ option.name }} {{ option.last_name }}
								</div>
							</template>
						</v-select>
					</div>
					<div class="form-group col-12 col-md-3">
						<label for="headquarter_id">Sede</label>
								<select class="form-control custom-selec" id="headquarter_id" v-model="search_headquarter_id">
									<option selected value="">Todas</option>
									<option v-for="headquarter in headquarterList" :key="headquarter.id"
										:value="headquarter.id">
										{{ headquarter.headquarter }}
									</option>
								</select>
          </div>
					<div class="form-group col-12 col-md-2">
						<label for="">Mostrar {{ search_results }} resultados:</label>
						<input type="number" id="search_results" name="search_results" class="form-control" placeholder="Desde"
							v-model="search_results"/>
					</div>
        </div>
        <div class="form-row text-right m-auto">
          <div class="form-group ofsset-md-4 col-md-4">
            <button
              class="btn btn-success w-100"
              type="button"
              @click="listHeadquartersExpenses(1)"
            >
              <i class="bi bi-search"></i> Buscar
            </button>
          </div>
          <div class="form-group col-md-4 ">
            <download-excel class="btn btn-primary w-100" :fields="json_fields" :data="headquartersExpenseList.data"
              name="report-headquarters-expenses.xls" type="xls">
              <i class="bi bi-file-earmark-arrow-down-fill"></i> Descargar .xls
            </download-excel>
          </div>
        </div>
      </form>
    </div>
    <div class="page-content">
      <section class="table-responsive">
        <table class="table table-bordered table-sm">
          <thead>
            <tr class="text-center">
              <th>ID</th>
              <th>Sede</th>
              <th>Fecha</th>
							<th>Tipo de movimiento</th>
							<th>Tipo de salida</th>
							<th>Afectación</th>
              <th>Valor</th>
            </tr>
          </thead>
          <tbody
            v-if="
              headquartersExpenseList.data &&
              headquartersExpenseList.data.length > 0
            "
          >
            <tr v-for="r in headquartersExpenseList.data" :key="r.id">
							<td>{{ r.id }}</td>
              <td>{{ r.headquarter.headquarter }}</td>
              <td> {{ r.date }} </td>
							<td>{{ r.type_movement }}</td>
							<td>{{ r.type_output }}</td>
							<td>{{ r.description | affectation('expense') }}</td>
              <td class="text-right">{{ r.price | currency }}</td>
            </tr>
          </tbody>
        </table>
        <pagination
          :align="'center'"
          :data="headquartersExpenseList"
          :limit="2"
          @pagination-change-page="listHeadquartersExpenses"
        >
          <span slot="prev-nav"><i class="bi bi-chevron-double-left"></i></span>
          <span slot="next-nav"
            ><i class="bi bi-chevron-double-right"></i
          ></span>
        </pagination>
      </section>
    </div>
  </div>
</template>

<script>
import {dollarFilter} from '../../filters'
export default {
  data() {
    return {
      headquartersExpenseList: {},
      headquartersExpenseTotal: {},
      listUsers: [],
      search_from: "",
      search_to: "",
      search_type_output: "",
      search_type_movement: "",
			search_headquarter_id: "",
      search_user_id: "",
      now: moment().format('YYYY-MM-DD'),
      json_fields: {
        'Sede': {
          field: 'headquarter.headquarter',
          callback: (value) => value
        },
        'Fecha': {
					field: "date",
					callback: (value) => value
				},
				'Tipo de movimiento': {
          field: 'type_movement',
          callback: (value) => value
        },
        'Tipo de salida': {
          field: 'type_output',
          callback: (value) => value
        },
				'Afectación': {
          field: 'description',
          callback: (value) => {
            return this.$options.filters.affectation(value, 'expense');
          }
        },
        'Valor egresos': {
          field: 'price',
          callback: (value) => {
            return this.$options.filters.currency(value, 'export');
          }
        }
      },
			headquarterList: [],
			search_results: 15
    };
  },
  mounted() {
    this.listHeadquartersExpenses(1);
		this.listHeadquarters();
  },
  methods: {
    listHeadquartersExpenses(page = 1) {
      let me = this;

      let data = {
        page: page,
        from: this.search_from,
        to: this.search_to,
        user_id: this.search_user_id,
        type_output: this.search_type_output,
        type_movement: this.search_type_movement,
				headquarter_id: this.search_headquarter_id,
				results: this.search_results
      }

      axios
        .get(
          `api/reports/headquarters-expenses`,
          {
            params: data,
            headers: this.$root.config.headers,
          }
        )
        .then(function (response) {
          me.headquartersExpenseList = response.data.expenses;
          me.headquartersExpenseTotal = response.data.totals;
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
    onSearchUsers(search, loading) {
			if (search.length) {
				loading(true);
				axios.get(`api/users?user=${search}&page=1`, this.$root.config)
					.then((response) => {
						console.log(response.data)
						this.listUsers = (response.data.users.data);
						loading(false)
					})
					.catch(e => console.log(e))
			}
		},
  },
};
</script>
