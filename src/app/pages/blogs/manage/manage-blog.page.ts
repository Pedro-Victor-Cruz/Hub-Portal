import {Component} from '@angular/core';
import {ActivatedRoute, Router} from '@angular/router';
import {ContentComponent} from '../../../components/content/content.component';
import {FormBuilder, FormGroup, FormsModule, ReactiveFormsModule, Validators} from '@angular/forms';
import {FormErrorHandlerService} from '../../../components/form/form-error-handler.service';
import {InputComponent} from '../../../components/form/input/input.component';
import {Utils} from '../../../services/utils.service';
import {ButtonComponent} from '../../../components/form/button/button.component';
import {BlogService} from '../../../services/blog.service';
import {EditorComponent} from '../../../components/form/editor/editor.component';
import {ImageComponent} from '../../../components/form/image/image.component';
import {ToggleSwitchComponent} from '../../../components/form/toggle-switch/toggle-switch.component';

@Component({
  selector: 'app-manage',
  imports: [
    ContentComponent,
    FormsModule,
    ReactiveFormsModule,
    InputComponent,
    ButtonComponent,
    EditorComponent,
    ImageComponent,
    ToggleSwitchComponent,
  ],
  templateUrl: './manage-blog.page.html',
  standalone: true,
  styleUrl: './manage-blog.page.scss',
})
export class ManageBlogPage {
  protected idBlog: string | undefined = undefined;

  form: FormGroup;
  errors: { [key: string]: string } = {};
  loading: boolean = false;
  loadingPage: boolean = false;
  imageUrl: string | null = null; // URL da imagem atual
  deletedImage: boolean = false; // Indica se a imagem foi marcada para remoção
  suggestionsCategories: string[] = [];

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private fb: FormBuilder,
    private blogService: BlogService,
    private utils: Utils
  ) {
    this.idBlog = this.route.snapshot.params['id'];

    this.form = this.fb.group({
      title: ['', [Validators.required]],
      subtitle: ['', [Validators.required]],
      slug: ['', [Validators.required]],
      excerpt: ['', [Validators.required]],
      content: ['', [Validators.required]],
      category: ['', [Validators.required]],
      featured: [false, [Validators.required]],
      image: [null], // Campo para a nova imagem
      status: [true]
    });

    this.form.valueChanges.subscribe(() => {
      this.errors = FormErrorHandlerService.getErrorMessages(this.form);
    });

    this.load();
  }

  // Carrega os dados do blog se for uma edição
  load() {
    if (this.idBlog) {
      this.loadingPage = true;
      this.blogService.getBlog(this.idBlog).then((response) => {
        this.form.patchValue({
          ...response,
          status: response.status === 'active'
        });
        this.imageUrl = response.image ? response.image_url : null;
      }).finally(() => (this.loadingPage = false));
    }

    this.blogService.getCategories().then(result => this.suggestionsCategories = result);
  }

  // Envia o formulário
  onSubmit() {
    this.loading = true;

    // Cria um FormData para enviar os dados
    const formData = new FormData();

    // Adiciona os campos do formulário ao FormData
    Object.keys(this.form.controls).forEach((key) => {
      const value = this.form.get(key)?.value;
      if (value !== null && value !== undefined && key !== 'image') {
        if (key === 'status') {
          formData.append(key, value ? 'active' : 'inactive');
        } else {
          formData.append(key, value);
        }
      }
    });

    // Adiciona a nova imagem ao FormData, se existir
    const imageFile = this.form.get('image')?.value;
    if (imageFile instanceof File) {
      formData.append('image', imageFile);
    }

    // Adiciona o campo deleted_image ao FormData, se a imagem foi marcada para remoção
    if (this.deletedImage) {
      formData.append('deleted_image', 'true');
    }

    // Se for uma atualização, adiciona o campo _method com o valor PUT
    if (this.idBlog) {
      formData.append('_method', 'PUT');
    }

    // Define a ação (criação ou atualização)
    const action = this.idBlog
      ? this.blogService.updateBlog(this.idBlog, formData)
      : this.blogService.createBlog(formData);

    // Executa a ação
    action
      .then((response) => {
        if (this.idBlog) {
          window.location.reload(); // Recarrega a página após a atualização
        } else {
          this.router.navigate([response.data.id], {relativeTo: this.route}); // Redireciona para a página de edição
        }
      })
      .catch((err) => {
        this.errors = this.utils.handleErrorsForm(err, this.form, this.errors); // Trata erros do formulário
      })
      .finally(() => (this.loading = false));
  }


  // Remove a imagem
  onRemoveImage() {
    this.imageUrl = null; // Remove a URL da imagem
    this.deletedImage = true; // Marca a imagem para remoção
    this.form.get('image')?.setValue(null); // Limpa o campo de imagem
  }

  // Atualiza o slug com base no título
  createSlug(event: FocusEvent) {
    const value = (event.target as HTMLInputElement).value;
    this.form.patchValue({slug: Utils.slug(value)});
  }

  // Volta para a página anterior
  back() {
    const url = this.idBlog ? '../../' : '../';
    this.router.navigate([url], {relativeTo: this.route});
  }

  protected readonly FormErrorHandlerService = FormErrorHandlerService;
}
