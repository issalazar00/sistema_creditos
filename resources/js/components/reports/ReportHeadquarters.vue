<template>
	<div class="page">
		<div class="page-header">
			<h3>Reporte de Sedes</h3>
		</div>
		<div class="page-search d-flex justify-content-between p-4 border my-2">
			<form class="form-row col-12">
				<div class="form-group offset-md-2 col-md-3 col-sm-6 col-xs-6 ">
					<label for="">Mostrar {{ search_results }} resultados por página:</label>
					<input type="number" id="search_results" name="search_results" class="form-control" placeholder="Desde"
						v-model="search_results" max="1000" />
				</div>
				<div class="form-group col-md-3 col-sm-6 col-xs-6 ">
					<button class="btn btn-success w-100 mt-5" type="button" @click="listReportHeadquarters(1)">
						<i class="bi bi-search"></i> Buscar
					</button>
				</div>
				<div class="form-group  col-md-3">
					<download-excel class="btn btn-primary w-100 mt-5" :fields="json_fields" :data="ReportHeaquartersList.data"
						name="report-headquarters.xls" type="xls">
						<i class="bi bi-file-earmark-arrow-down-fill"></i> Descargar .xls
					</download-excel>
				</div>
			</form>
		</div>
		<div class="page-content">
			<section class="table-responsive">
				<table class="table table-sm table-bordered">
					<thead>
						<tr class="text-center">
							<th>#</th>
							<th>Sede</th>
							<th>Nro. Creditos</th>
							<th>Pendientes</th>
							<th>Pendientes con proveedor</th>
							<th>Activos</th>
							<th>Rechazados</th>
							<th>Completado</th>
							<th>Cobro jurídico</th>
							<th>Monto Crédito</th>
							<th>Capital Abonado</th>
							<th>Interés abonado</th>
							<th>Interés Cobro & Admin abonado</th>
						</tr>
					</thead>
					<tbody v-if="ReportHeaquartersList.data">
						<tr v-for="(report, index) in ReportHeaquartersList.data" :key="index">
							<td>{{ index + 1 }}</td>
							<td>{{ report.headquarter }}</td>
							<td>{{ report.number_of_credits }}</td>
							<td>{{ report.pending }}</td>
							<td>{{ report.pending_provider }}</td>
							<td>{{ report.approved }}</td>
							<td>{{ report.rejected }}</td>
							<td>{{ report.completed }}</td>
							<td>{{ report.legal_recovery }}</td>
							<td class="text-right">{{ report.credit_value | currency }}</td>
							<td class="text-right">{{ report.capital_value | currency }}</td>
							<td class="text-right">{{ report.interest_value | currency }}</td>
							<td class="text-right">{{ report.additional_interest_paid | currency }}</td>
							
						</tr>
					</tbody>
				</table>
				<pagination :align="'center'" :data="ReportHeaquartersList" :limit="2"
					@pagination-change-page="listReportHeadquarters">
					<span slot="prev-nav"><i class="bi bi-chevron-double-left"></i></span>
					<span slot="next-nav"><i class="bi bi-chevron-double-right"></i></span>
				</pagination>
			</section>
		</div>
	</div>
</template>

<script>
export default {
	data() {
		return {
			ReportHeaquartersList: {},
			search_results: 15,
			json_fields: {
				'Sede': {
					field: 'headquarter',
					callback: (value) => {
						return value;
					}
				},
				'Nro. Creditos': {
					field: 'number_of_credits',
					callback: (value) => {
						return value;
					}
				},
				'Pendientes': {
					field: 'pending',
					callback: (value) => {
						return value;
					}
				},
				'Pendientes con proveedor': {
					field: 'pending_provider',
					callback: (value) => {
						return value;
					}
				},
				'Activos': {
					field: 'approved',
					callback: (value) => {
						return value;
					}
				},
				'Rechazados': {
					field: 'rejected',
					callback: (value) => {
						return value;
					}
				},
				'Completado': {
					field: 'completed',
					callback: (value) => {
						return value;
					}
				},
				'Monto Créditos': {
					field: 'credit_value',
					callback: (value) => {
						return this.$options.filters.currency(value, 'export');
					}
				},
				'Capital Abonado': {
					field: 'capital_value',
					callback: (value) => {
						return this.$options.filters.currency(value, 'export');
					}
				},
				'Interés abonado': {
					field: 'interest_value',
					callback: (value) => {
						return this.$options.filters.currency(value, 'export');
					}
				},
				'Interés Cobro & Admin abonado': {
					field: 'additional_interest_paid',
					callback: (value) => {
						return this.$options.filters.currency(value, 'export');
					}
				},
			}
		};
	},
	methods: {
		listReportHeadquarters(page = 1) {
			let data = {
				page: page,
				results: this.search_results
			};
			axios
				.get(`api/reports/headquarters`, {
					params: data,
					headers: this.$root.config.headers,
				})
				.then((response) => {
					this.ReportHeaquartersList = response.data;
				});
		},
	},
	mounted() {
		this.listReportHeadquarters(1);
	},
};
</script>
