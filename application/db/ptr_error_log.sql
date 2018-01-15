CREATE TABLE public.ptr_error_log
(
  ptr_error_id SERIAL,
  ptr_error text,
  CONSTRAINT ptr_error_id_pkey PRIMARY KEY (ptr_error_id)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE public.ptr_error_log
  OWNER TO ixmaps;
GRANT ALL ON TABLE public.ptr_error_log TO ixmaps;
