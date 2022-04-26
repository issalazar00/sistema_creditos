<template>
	<div class="card">
		<div class="card-header text-center">
			<h5>Caja principal</h5>
		</div>
		<div class="card-body">
			<form class="form-row">
				<div class="form-group row col-md-6">
					<label for="saldo_inicial" class="col-sm-4 col-form-label"
						>Saldo inicial</label
					>
					<div class="col-sm-8">
						<input
							type="text"
							readonly
							class="form-control"
							id="saldo_inicial"
							placeholder="$"
							:value="formMainBox.initial_balance | currency"
						/>
					</div>
				</div>

				<div class="form-group row col-md-6">
					<label for="saldo_inicial" class="col-sm-4 col-form-label"
						>Saldo actual</label
					>
					<div class="col-sm-8">
						<input
							type="text"
							readonly
							class="form-control"
							id="saldo_inicial"
							placeholder="$"
							:value="formMainBox.current_balance | currency"
						/>
					</div>
				</div>

				<div class="form-group row col-md-6">
					<label for="input" class="col-sm-4 col-form-label">Entradas</label>
					<div class="col-sm-8">
						<input
							type="text"
							readonly
							class="form-control"
							id="input"
							placeholder="$"
							:value="formMainBox.input | currency"
						/>
					</div>
				</div>
				<div class="form-group row col-md-6">
					<label for="output" class="col-sm-4 col-form-label">Salidas</label>
					<div class="col-sm-8">
						<input
							type="text"
							readonly
							class="form-control"
							id="output"
							placeholder="$"
							:value="formMainBox.output | currency"
						/>
					</div>
				</div>
				<div class="form-group row col-md-6">
					<label for="last_update" class="col-sm-4 col-form-label"
						>Última modificacion</label
					>
					<div class="col-sm-8">
						<input
							type="text"
							class="form-control"
							readonly
							disabled
							id="last_update"
							v-model="formMainBox.last_update"
							v-if="formMainBox.last_editor"
						/>
					</div>
				</div>

				<div class="form-group row col-md-6" v-if="formMainBox.last_editor">
					<label for="last_update" class="col-sm-4 col-form-label"
						>Último editor</label
					>
					<div class="col-sm-8">
						{{ lastEditor.name }} {{ lastEditor.last_name }}
					</div>
				</div>
				<div class="form-group row col-md-6 h5">
					<label for="saldo_inicial" class="col-sm-4 col-form-label"
						>Añadir saldo: <b>{{ add_amount | currency}}</b></label
					>
					<div class="col-sm-8">
						<input
							type="text"
							class="form-control form-control-lg"
							id="saldo_inicial"
							placeholder="$"
							v-model="add_amount"
						/>
						<small class="form-text text-danger">
							{{ formErrors.amount}}
						</small>
					</div>
				</div>
				<div class="w-100 text-center mt-5" v-if="$root.validatePermission('box-update')">
					<button
						class="btn btn-primary"
						type="button"
						style="min-width: 30%"
						@click="updateBox()"
					>
						Guardar
					</button>
				</div>
			</form>
		</div>
	</div>
</template>

<script>
export default {
	data() {
		return {
			formMainBox: {},
			lastEditor: {},
			add_amount: 0,
			formErrors: {
				amount: ""
			},
		};
	},
	created() {
		this.getMainBox();
	},
	methods: {
		getMainBox() {
			axios.get("api/main-box", this.$root.config).then((response) => {
				this.formMainBox = response.data.main_box;
				this.lastEditor = response.data.last_editor;
			});
			this.$root.getCurrentBalanceMainBox();
		},
		updateBox() {
			let me = this;
			if (this.box_id != 0) {
				me.$root.assignErrors(false, me.formErrors);
				axios
					.put(`api/main-box/${this.formMainBox.id}`, {
						amount: this.add_amount,
					}, me.$root.config)
					.then(function () {
						me.getMainBox();
					}).catch(response => {
						me.$root.assignErrors(response, me.formErrors);
					})
			}
		},
	},
};
</script>
