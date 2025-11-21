defmodule App.Accounts do
  @moduledoc """
  The Accounts context.
  """

  import Ecto.Query, warn: false
  alias App.Repo
  alias App.Accounts.User

  @sortable_fields %{
    "first_name" => :first_name,
    "last_name"  => :last_name,
    "birthdate"  => :birthdate,
    "gender"     => :gender
  }

  @doc """
  Returns the list of users with optional filtering and sorting.
  """
  def list_users(params \\ %{}) do
    cleaned_params =
      params
      |> Enum.reject(fn {_, v} -> v in [nil, ""] end)
      |> Map.new()

    User
    |> filter_by_name(cleaned_params["first_name"])
    |> filter_by_last_name(cleaned_params["last_name"])
    |> filter_by_gender(cleaned_params["gender"])
    |> filter_by_date(cleaned_params["birthdate_from"], cleaned_params["birthdate_to"])
    |> sort_users(cleaned_params["sort_by"], cleaned_params["sort_order"])
    |> Repo.all()
  end

  # --- Filters ---

  defp filter_by_name(query, nil), do: query
  defp filter_by_name(query, name),
    do: where(query, [u], ilike(u.first_name, ^"%#{name}%"))

  defp filter_by_last_name(query, nil), do: query
  defp filter_by_last_name(query, name),
    do: where(query, [u], ilike(u.last_name, ^"%#{name}%"))

  defp filter_by_gender(query, nil), do: query
  defp filter_by_gender(query, gender),
    do: where(query, [u], u.gender == ^gender)

  # --- Date range filter ---

  defp filter_by_date(query, nil, nil), do: query
  defp filter_by_date(query, from, to) do
    query
    |> apply_date_filter(:gte, from)
    |> apply_date_filter(:lte, to)
  end

  defp apply_date_filter(query, _, nil), do: query

  defp apply_date_filter(query, :gte, date_str) do
    case Date.from_iso8601(date_str) do
      {:ok, date} -> where(query, [u], u.birthdate >= ^date)
      _ -> query
    end
  end

  defp apply_date_filter(query, :lte, date_str) do
    case Date.from_iso8601(date_str) do
      {:ok, date} -> where(query, [u], u.birthdate <= ^date)
      _ -> query
    end
  end

  # --- Sorting ---

  defp sort_users(query, nil, _), do: query
  defp sort_users(query, column, order) do
    case Map.get(@sortable_fields, column) do
      nil ->
        query

      atom_field ->
        direction = if order == "desc", do: :desc, else: :asc
        order_by(query, [u], [{^direction, field(u, ^atom_field)}])
    end
  end

  # --- CRUD ---

  def get_user!(id), do: Repo.get!(User, id)

  def create_user(attrs \\ %{}) do
    %User{}
    |> User.changeset(attrs)
    |> Repo.insert()
  end

  def update_user(%User{} = user, attrs) do
    user
    |> User.changeset(attrs)
    |> Repo.update()
  end

  def delete_user(%User{} = user) do
    Repo.delete(user)
  end

  def change_user(%User{} = user, attrs \\ %{}) do
    User.changeset(user, attrs)
  end
end
