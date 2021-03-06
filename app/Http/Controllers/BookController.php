<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\Lending;
use App\Models\Author;
use Validator;

class BookController extends Controller
{
    private $path= 'images/book';

     public function __construct()
    {
        //manda para o middleware para saber se estou autenticado
        $this->middleware('auth.admin');
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //selec * from books
        $books= Book::paginate(10);
        return view('book.index', compact('books'));
    }

    //cria a view para visualizar
    public function add()
    {
        $authors = Author::get();
        return view('book.add', compact('authors'));
    }

    public function save(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'title' => 'required',
            'authors' => 'required',
        ],[
            'required' => 'Campo :attribute é obrigatório'
        ],[
            'title' => 'Título',
            'authors' => 'Autores',
        ]);

        if($validator->fails()){
             return redirect()->back()->withErrors($validator)->withInput();
        }
        //dd mata a aplicação e formata  e mostra conteudo da variaver
        //dd(variavel);
        if (!empty($request->file('image')) && $request->file('image')->isValid()) {
           //crio um nome para o arquivo timestamp + a extensao do arqui 
            $fileName = time().'.'.$request->file('image')->getClientOriginalExtension();
            //move o arquivo da pasta temporaria e move para o servidor com o novo nome
            $request ->file('image')->move($this->path,$fileName);
        }else{
            $fileName = null;
        }

        $book = book::create([
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'image' =>$fileName
        ]);

        $authors = $request->input('authors');
        if (!empty($authors))
        {
            $book->authors()->sync($authors);
        }

        return redirect()->route('book.index');
    }


    public function edit($id)
    {
        $book = Book::find($id);

        if(!empty($book)){
            $authors = Author::get();
            $selecteds_author = array();

            foreach ($book->authors as $author) {
                $selecteds_author[] = $author->authorship->author_id;
            } 

            return view('book.edit', compact('book', 'authors', 'selecteds_author'));  
        }
        return redirect()->route('book.index');
    }

    public function update(Request $request, $id)
    {
        
        $validator = Validator::make($request->all(),[
            'title' => 'required',
            'authors' => 'required',
        ],[
            'required' => 'Campo :attribute é obrigatório'
        ],[
            'title' => 'Título',
            'authors' => 'Autores',
        ]);

        if($validator->fails()){
             return redirect()->back()->withErrors($validator)->withInput();
        }
        $fileName = null;

        $book = Book::find($id);
        if(!empty( $book ))
        {
            if(!empty($request->file('image')) && $request->file('image')->isValid()){
                if(!empty($request->input('deleteimage')) && file_exists($this->path . '/' . $request->input('deleteimage'))){
                    unlink($this->path . '/' . $request->input('deleteimage'));
                }

                $fileName = time() . '.' . $request->file('image')->getClientOriginalExtension();

                $request->file('image')->move($this->path,$fileName);
            }

            if(!$fileName){
                $update = [
                    'title' => $request->input('title'),
                    'description' => $request->input('description'),
                ];
            }else{
                $update = [
                    'title' => $request->input('title'),
                    'description' => $request->input('description'),
                    'image' => $fileName
                ];
            }
            $result = $book->update($update);
            $authors = $request->input('authors');
            if (!empty($authors))
            {
                $book->authors()->sync($authors);
            }
        }

        return redirect()->route('book.index');
    }

    public function search(Request $request){
        $title = $request->input('title');
        $search = TRUE;

        if(($title) && (!empty($title))){
            $books = Book::where('title','like','%'.$title.'%')->get();
            return view('book.index', compact('books', 'search'));
        }else{
            return redirect()->route('book.index');
        }
    }

    public function delete( Request $request )
    {
        $id = $request->input('id');
        $book = Book::find($id);

        if($book){
            $book->authors()->detach();
            $book->lendings()->detach();
            $result = $book->delete();
        }
        $returnHtml = '../livros';
        return response()->json([
            'success' => true,
            'html' => $returnHtml,
        ], 200);
    }

}
