<?php

namespace Tower;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use stdClass;

class JsonResource
{
    protected Collection|Paginator|stdClass|null $data;

    protected array $response = [];

    protected array $meta = [];

    protected ?int $perPage = null;

    public function __construct(Collection|Paginator|stdClass|array|null $data , int $perPage = null)
    {
        if (is_array($data))
            $data = $this->arrayToCollection($data);

        $this->data = $data;
        $this->perPage = $perPage;
    }

    public function reletional(): array
    {
        $this->process();

        return $this->response;
    }

    protected function arrayToCollection(array $data): Collection
    {
        $collection = new Collection();

        foreach ($data as $item)
            $collection->add((object) $item);

        return $collection;
    }

    public function collection(): Response
    {
        $this->process();

        if (is_null($this->perPage))
            return json([
                'data' => $this->response ,
            ]);

        $this->addToMeta([
            'isLastPage' => count($this->data) < $this->perPage ,
            'perPage' => $this->perPage
        ]);

        return json([
            'data' => $this->response ,
            'meta' => $this->meta
        ]);
    }

    public function single(): Response
    {
        if (is_null($this->data))
            return json([
                'data' => []
            ]);

        if ($this->data instanceof Collection)
            return json([
                'data' => $this->toArray($this->data->first())
            ]);

        return json([
            'data' => $this->toArray($this->data)
        ]);
    }

    protected function process(): void
    {
        if (! is_null($this->data))
            $this->data->map(function ($data){
                array_push($this->response , $this->toArray($data));
            });
    }

    public function addToMeta(array $values): self
    {
        foreach ($values as $key => $value)
            $this->meta[$key] = $value;

        return $this;
    }

    protected function toArray($request)
    {
        return $request;
    }
}