@extends('layouts.admin')

@section('page-title', 'Edit Article')

@section('page-content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Edit Article: {{ $article->title }}</h1>
        <a href="{{ route('admin.knowledge-base.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Articles
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.knowledge-base.update', $article) }}">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Article Content</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror"
                                   id="title" name="title" value="{{ old('title', $article->title) }}" required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="slug" class="form-label">Slug</label>
                            <input type="text" class="form-control @error('slug') is-invalid @enderror"
                                   id="slug" name="slug" value="{{ old('slug', $article->slug) }}">
                            @error('slug')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="excerpt" class="form-label">Excerpt</label>
                            <textarea class="form-control @error('excerpt') is-invalid @enderror"
                                      id="excerpt" name="excerpt" rows="2">{{ old('excerpt', $article->excerpt) }}</textarea>
                            @error('excerpt')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="body" class="form-label">Body <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('body') is-invalid @enderror"
                                      id="body" name="body" rows="15" required>{{ old('body', $article->body) }}</textarea>
                            <div class="form-text">Supports Markdown formatting.</div>
                            @error('body')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Publishing</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="category_id" class="form-label">Category</label>
                            <select class="form-select @error('category_id') is-invalid @enderror"
                                    id="category_id" name="category_id">
                                <option value="">Uncategorized</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @selected(old('category_id', $article->category_id) == $category->id)>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="sort_order" class="form-label">Sort Order</label>
                            <input type="number" class="form-control" id="sort_order" name="sort_order"
                                   value="{{ old('sort_order', $article->sort_order) }}" min="0">
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_published" name="is_published"
                                       value="1" @checked(old('is_published', $article->is_published))>
                                <label class="form-check-label" for="is_published">Published</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured"
                                       value="1" @checked(old('is_featured', $article->is_featured))>
                                <label class="form-check-label" for="is_featured">Featured article</label>
                            </div>
                        </div>

                        <hr>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Update Article
                            </button>
                            <a href="{{ route('admin.knowledge-base.index') }}" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Tags</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-0">
                            <input type="text" class="form-control" id="tags" name="tags"
                                   value="{{ old('tags', $article->tags?->pluck('name')->implode(', ')) }}" placeholder="Comma-separated tags">
                            <div class="form-text">e.g. setup, installation, configuration</div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Article Info</h5>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-5">Created</dt>
                            <dd class="col-sm-7">{{ $article->created_at->format('M d, Y') }}</dd>
                            <dt class="col-sm-5">Updated</dt>
                            <dd class="col-sm-7">{{ $article->updated_at->format('M d, Y') }}</dd>
                            <dt class="col-sm-5">Author</dt>
                            <dd class="col-sm-7">{{ $article->author?->name ?? '—' }}</dd>
                            <dt class="col-sm-5">Views</dt>
                            <dd class="col-sm-7">{{ number_format($article->views_count ?? 0) }}</dd>
                        </dl>
                    </div>
                </div>

                <div class="card mt-4 border-danger">
                    <div class="card-header bg-danger text-white">
                        <h5 class="card-title mb-0">Danger Zone</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small">Permanently delete this article.</p>
                        <form method="POST" action="{{ route('admin.knowledge-base.destroy', $article) }}"
                              onsubmit="return confirm('Are you sure you want to delete this article? This cannot be undone.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">
                                <i class="bi bi-trash"></i> Delete Article
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
