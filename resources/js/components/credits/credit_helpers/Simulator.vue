<template>
	<div class="">
		<div class="card-body">
			<div>
				<div class="">
					<button
						type="button"
						class="btn btn-primary col-md-3 mb-5 ml-auto"
						id="btnCalcular"
						@click="simulateInstallments()"
					>
						Calcular
					</button>

					<section class="table-responsive">
						<table id="lista-tabla" class="table table-bordered">
							<thead>
								<tr class="text-center">
									<th>Nro.Cuota</th>
									<th>Fecha</th>
									<th>Cuota</th>
									<th>Capital</th>
									<th>Interés</th>
									<th>Saldo Capital</th>
								</tr>
							</thead>
							<tbody v-if="listInstallments.length">
								<tr
									v-for="(installment, index) in listInstallments"
									:key="installment.installment_number"
								>
									<td>No. {{ index + 1 }}</td>
									<td>{{ installment.payment_date }}</td>
									<td class="text-right">
										{{ installment.installment_value | currency }}
									</td>
									<td class="text-right">
										{{ installment.pagoCapital | currency }}
									</td>
									<td class="text-right">
										{{ installment.pagoInteres | currency }}
									</td>
									<td class="text-right">
										{{ installment.saldo_capital | currency }}
									</td>
								</tr>
							</tbody>
						</table>
					</section>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
export default {
	props: ["number_installments", "interest", "capital", "start_date"],
	// capital value total del prestamo
	//tasa de interest que se compraria
	// plazos numero de pagos
	data() {
		return {
			editar: false,
			listInstallments: [],
			formInstallments: {},
			installment_value: 0,
		};
	},
	methods: {
		createSimulator() {
			let me = this;
			axios
				.post("api/installments", this.installments, me.$root.config)
				.then(function () {
					$("#formSimulatorModal").modal("hide");
					me.resetData();
					this.$emit("list-credits");
				});
		},
		openSimulator(credit) {
			this.editar = true;
			let me = this;
			$("#formSimulatorModal").modal("show");
			me.formSimulator = credit;
		},
		editSimulator() {
			let me = this;
			axios
				.put("api/credits/" + 4, this.installments, me.$root.config)
				.then(function () {
					$("#formSimulatorModal").modal("hide");
					me.resetData();
				});
			this.$emit("list-credits");

			this.editar = false;
		},
		simular: function (id) {
			let me = this;
			axios
				.post("api/credits/" + id + "/installments", null, me.$root.config)
				.then(function () {
					me.listCredits(1);
				});
		},
		resetData() {
			let me = this;
			Object.keys(this.installments).forEach(function (key, index) {
				me.installments[key] = "";
			});
		},
		agregar() {
			this.formInstallments.push(newInstallment);
			console.log(agregar);
		},
		simulateInstallments() {
			let me = this;
			axios
				.get(
					`api/installments/calculate-installments?credit_value=${this.capital}&interest=${this.interest}&number_installments=${this.number_installments}&start_date=${this.start_date}`
				)
				.then(
					(response) => (
						(me.listInstallments = response.data.listInstallments),
						(me.installment_value = response.data.installment)
					)
				);
		},
	},
	mounted() {
		// this.simulateInstallments();
	},
};
</script>
