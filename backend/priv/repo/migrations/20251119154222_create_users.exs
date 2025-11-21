defmodule App.Repo.Migrations.CreateUsers do
  use Ecto.Migration

  def change do
    create table(:users) do
      add :first_name, :string
      add :last_name, :string
      add :birthdate, :date
      add :gender, :string

      timestamps()
    end
  end
end
