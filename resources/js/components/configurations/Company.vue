<template>
	<div class="col-12">
		<h3 class="text-center page-header">Configuración</h3>
		<div class="page-content mt-4" style="width: 100%">
			<div class="d-flex justify-content-center">
				<form id="form_configuration" action="#" @submit.prevent="saveConfiguration">
					<div class="form-row">
						<div class="form-group col-12 col-md-6">
							<label for="name">Nombre de la empresa</label>
							<input type="text" class="form-control" id="name" name="name" placeholder="Ingresar nombre"
								:value="formConfiguration.name" />
							<small id="nameHelp" class="form-text text-danger">{{
								formErrors.name
							}}</small>
						</div>
						<div class="form-group col-12 col-md-6">
							<label for="method">Método de amortización</label>
							<select name="method" id="method" class="custom-select" :value="formConfiguration.method">
								<option value="FRANCHISE">Sistema de amortización Francés</option>
								<option value="GENERAL">Sistema de amortización general</option>
							</select>

							<small id="methodHelp" class="form-text text-danger">{{
								formErrors.method
							}}</small>
						</div>
						<div class="form-group col-12 col-md-6">
							<label for="legal_representative">Representante legal</label>
							<input type="text" class="form-control" id="legal_representative" name="legal_representative"
								placeholder="Ingresar representante legal" :value="formConfiguration.legal_representative" />
							<small id="legal_representativeHelp" class="form-text text-danger">{{ formErrors.legal_representative
							}}</small>
						</div>
					</div>
					<div class="form-row">
						<div class="form-group col-12 col-md-6">
							<label for="nit">Nit</label>
							<input type="text" class="form-control" id="nit" name="nit" placeholder="Ingresar Nit"
								:value="formConfiguration.nit" />
							<small id="nitHelp" class="form-text text-danger">{{
								formErrors.nit
							}}</small>
						</div>
						<div class="form-group col-12 col-md-6">
							<label for="address">Dirección</label>
							<input type="text" class="form-control" id="address" name="address" placeholder="Ingresar dirección"
								:value="formConfiguration.address" />
							<small id="addressHelp" class="form-text text-danger">{{
								formErrors.address
							}}</small>
						</div>
					</div>
					<div class="form-row">
						<div class="form-group col-12 col-md-6">
							<label for="email">Email</label>
							<input type="email" class="form-control" id="address" name="email" placeholder="Ingresar email"
								:value="formConfiguration.email" />
							<small id="emailHelp" class="form-text text-danger">{{
								formErrors.email
							}}</small>
						</div>

						<div class="form-group col-12 col-md-6">
							<label for="telephone">Teléfono</label>
							<input type="tel" class="form-control" id="telephone" name="telephone" placeholder="Ingresar teléfono"
								:value="formConfiguration.telephone" />
							<small id="telephoneHelp" class="form-text text-danger">{{
								formErrors.telephone
							}}</small>
						</div>
						<div class="form-group col-12 col-md-6">
							<label for="mobile">Celular</label>
							<input type="tel" class="form-control" id="mobile" name="mobile" placeholder="Ingresar celular"
								:value="formConfiguration.mobile" />
							<small id="mobileHelp" class="form-text text-danger">{{
								formErrors.mobile
							}}</small>
						</div>
						<div class="form-group col-12 col-md-6">
							<label for="mobile">Mensaje de Whatsapp para clientes</label>
							<textarea class="form-control" name="whatsapp_msg" id="whatsapp_msg" cols="30" rows="4"
								:value="formConfiguration.whatsapp_msg"></textarea>
							<small id="whatsapp_msgHelp" class="form-text text-danger">{{
								formErrors.whatsapp_msg
							}}</small>
						</div>

						<div class="form-group col-12 col-md-6">
							<label for="mobile">Condiciones de ticket</label>
							<textarea class="form-control" name="condition_order" id="condition_order" cols="30" rows="4"
								:value="formConfiguration.condition_order"></textarea>
							<small id="condition_orderHelp" class="form-text text-danger">{{
								formErrors.condition_order
							}}</small>
						</div>
						<div class="form-group col-12 col-md-6">
							<label for="mobile">Condiciones de cotización</label>
							<ckeditor :editor="editor" :config="editorConfig" v-model="formConfiguration.condition_quotation"
								:tagName="'textarea'" id="condition_quotation" name="condition_quotation"></ckeditor>
							<small id="condition_quotationHelp" class="form-text text-danger">{{ formErrors.condition_quotation
							}}</small>
						</div>
						<div class="form-group col-12 col-md-6">
							<label for="late_interest_day">Porcentaje de interes por día</label>
							<input type="number" id="late_interest_day"  class="form-control" name="late_interest_day" min="0" step="any" :value="formConfiguration.late_interest_day" placeholder="Ingresar interes">
							<small id="late_interest_dayHelp" class="form-text text-danger">{{
								formErrors.late_interest_day
							}}</small>
						</div>
						<div class="form-group col-12 col-md-6">
							<label for="logo">Logo</label>
							<div class="p-0 border">
								<div style="height: 230px" class="d-flex justify-content-center my-2">
									<img id="image" class="border" style="height: 230px; width: 200px; object-fit: cover"
										:src="formConfiguration.logo" alt="logo" />
								</div>
								<label class="btn border btn-block mb-0">
									<i class="bi bi-pen"></i>
									<input id="logo" name="file0" data-info="image" type="file" style="display: none" @change="(event) => {
											readImage(event.target);
										}
										" />
								</label>
							</div>
							<small id="logoHelp" class="form-text text-danger">{{
								formErrors.file0
							}}</small>
						</div>
					</div>

					<div class="form-group text-right">
						<button type="submit" class="btn btn-primary btn-lg w-25">Guardar</button>
					</div>
				</form>
			</div>
		</div>
	</div>
</template>
<script>
import ClassicEditor from "@ckeditor/ckeditor5-build-classic";
import "@ckeditor/ckeditor5-build-classic/build/translations/es";

export default {
	data() {
		return {
			formConfiguration: {
				name: "",
				legal_representative: "",
				nit: "",
				address: "",
				email: "",
				telephone: "",
				mobile: "",
				logo: "",
				condition_order: "",
				condition_quotation: "",
				whatsapp_msg: "",
				method: 'FRANCHISE',
				late_interest_day:""
			},
			formErrors: {
				name: "",
				legal_representative: "",
				nit: "",
				address: "",
				email: "",
				telephone: "",
				mobile: "",
				file0: "",
				condition_order: "",
				condition_quotation: "",
				whatsapp_msg: "",
				method: "",
				late_interest_day:""
			},
			editor: ClassicEditor,
			editorConfig: {
				toolbar: {
					removeItems: [
						"uploadImage",
						"mediaEmbed",
						"blockQuote",
						"insertTable",
						"outdent",
						"indent",
						"link",
					],
					shouldNotGroupWhenFull: true,
				},
				language: "es",
			},
		};
	},
	created() {
		this.getConfiguration();
	},
	methods: {
		getConfiguration() {
			axios.get("api/configurations", this.$root.config).then((response) => {
				if (response.data.company) {
					this.formConfiguration = response.data.company;
					this.formConfiguration.condition_quotation =
						this.formConfiguration.condition_quotation ?? "";
				}
			});
		},
		saveConfiguration() {
			let me = this;

			var form = new FormData($("#form_configuration")[0]);
			form.append("id", me.formConfiguration.id);
			form.set(
				"condition_quotation",
				me.formConfiguration.condition_quotation
			);

			me.$root.assignErrors(false, me.formErrors);

			axios
				.post("api/configurations", form, me.$root.config)
				.then((response) => {
					console.log(response.data);
					me.formConfiguration = response.data.company;
					me.$root.assignErrors(false, me.formErrors);
					console.log(me.formConfiguration);

				})
				.catch((response) => {
					me.$root.assignErrors(response, me.formErrors);
				});
		},

		readImage(input) {
			var id = $(input).data("info");
			if (input.files && input.files[0]) {
				var reader = new FileReader();
				reader.onload = function (e) {
					$(`#${id}`).attr("src", e.target.result);
				};
				reader.readAsDataURL(input.files[0]);
			}
		},
	},
};
</script>
