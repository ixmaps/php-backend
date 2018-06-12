CREATE TABLE public.ptr_contributions
(
  CONSTRAINT ptr_id_pkey PRIMARY KEY (ptr_id)
  ptr_id SERIAL,
  ptr_json json,
  request_id INT
)
WITH (
  OIDS=FALSE
);
ALTER TABLE public.ptr_contributions
  OWNER TO ixmaps;
GRANT ALL ON TABLE public.ptr_contributions TO ixmaps;
